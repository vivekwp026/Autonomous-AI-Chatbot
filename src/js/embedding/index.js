import { env, AutoTokenizer } from '@xenova/transformers';

export const chunkText = (text, chunkSize = 300) => {
  const words = text.split(/\s+/);
  const chunks = [];
  for (let i = 0; i < words.length; i += chunkSize) {
    chunks.push(words.slice(i, i + chunkSize).join(' '));
  }

  return chunks;
}

export const l2Normalize = (arr) => {
  let s = 0;
  for (let i = 0; i < arr.length; i++) s += arr[i] * arr[i];
  s = Math.sqrt(s) + 1e-12;
  const out = new Float32Array(arr.length);
  for (let i = 0; i < arr.length; i++) out[i] = arr[i] / s;
  return out;
}

export const meanPoolFrom3d = (tensor, attentionMaskArray) => {
  const [batch, seqLen, hidden] = tensor.dims;
  const data = tensor.data;
  const poolOut = [];
  for (let b = 0; b < batch; b++) {
    const emb = new Float32Array(hidden);
    let count = 0;
    for (let i = 0; i < seqLen; i++) {
      const maskVal = Number(attentionMaskArray[b * seqLen + i] || 0n);
      if (maskVal === 0) continue;
      count++;
      const base = (b * seqLen + i) * hidden;
      for (let h = 0; h < hidden; h++) emb[h] += data[base + h];
    }
    if (count === 0) count = 1;
    for (let h = 0; h < hidden; h++) emb[h] /= count;
    poolOut.push(l2Normalize(emb));
  }

  return poolOut;
}

export const embedChunk = async (text, session, onnxModalDir, tokenizer) => {
    try {
        

        const maxTokens = 512;
        const enc = await tokenizer([text], { addSpecialTokens: true, truncation: true, max_length: maxTokens });
        

        if (enc.input_ids.data.length > maxTokens) {
            enc.input_ids.data = enc.input_ids.data.slice(0, maxTokens);
            enc.attention_mask.data = enc.attention_mask.data.slice(0, maxTokens);
            enc.input_ids.dims = [1, enc.input_ids.data.length];
            enc.attention_mask.dims = [1, enc.attention_mask.data.length];
            
        }

        const tokenTypeIds = new BigInt64Array(enc.input_ids.data.length).fill(0n);
        const feeds = {
            input_ids: new ort.Tensor('int64', enc.input_ids.data, enc.input_ids.dims),
            attention_mask: new ort.Tensor('int64', enc.attention_mask.data, enc.attention_mask.dims),
            token_type_ids: new ort.Tensor('int64', tokenTypeIds, enc.input_ids.dims)
        };
        
        const results = await session.run(feeds);
        

        const outTensor = results[Object.keys(results)[0]];

        let normalized;
        if (outTensor.dims.length === 2) {
            const emb = outTensor.data.slice(0, outTensor.dims[1]);
            
            normalized = l2Normalize(emb);
            
        } else {        
            const pooled = meanPoolFrom3d(outTensor, enc.attention_mask.data)[0];
            normalized = pooled;
        }

        // Dispose tensors to free WASM memory
        try {
            feeds.input_ids.dispose?.();
            feeds.attention_mask.dispose?.();
            feeds.token_type_ids.dispose?.();
            outTensor.dispose?.();
        } catch (e) {
            console.log("   Note: Tensor disposal not available");
        }

        return normalized;
    } catch (err) {
        console.log("    embedChunk ERROR:", err.message);
        console.log("   Stack:", err.stack);
        throw err;
    }
}

export const createEmbedding = async (text, session, onnxModalDir) => {
    try {
       
        
        
        env.localModelPath = onnxModalDir;
        const tokenizer = await AutoTokenizer.from_pretrained('intfloat/e5-small-v2');
        
        
        const chunks = chunkText(text);
        
        
        let avg = null, validChunks = 0;

        for (let i = 0; i < chunks.length; i++) {
            const chunk = chunks[i];
            
            try {
                // Pass cached tokenizer instead of loading it each time
                const emb = await embedChunk(chunk, session, onnxModalDir, tokenizer);
                
                
                if (!avg) {
                    avg = new Float32Array(emb.length);
                    
                }
                
                for (let h = 0; h < emb.length; h++) {
                    avg[h] += emb[h];
                }
                validChunks++;
                
            
                
                if (i < chunks.length - 1) {
                
                    await new Promise(resolve => setTimeout(resolve, 150));
                }
            } catch (err) {
                console.log(`Skipping chunk ${i + 1}: ${err.message}`);
            }
        }

        if (validChunks === 0) {
            const error = new Error("No valid embeddings generated.");
            console.log('CRITICAL: ' + error.message);
            throw error;
        }
        
        
        for (let h = 0; h < avg.length; h++) {
            avg[h] /= validChunks;
        }
        
        
        const normalized = l2Normalize(avg);
       
        return normalized;
    } catch (err) {
        console.log(`ERROR in createEmbedding: ${err.message}`);
        console.log('Stack trace:', err.stack);
        throw err;
    }
}

export const createTernlightEmbedding = async (text) => {
    try {
        const { embed } = await import('@ternlight/base');
        const chunks = chunkText(text, 80); // 80 words fits well within the 128-token limit
        let avg = null;
        let validChunks = 0;

        for (let i = 0; i < chunks.length; i++) {
            const chunk = chunks[i];
            try {
                const emb = embed(chunk);
                if (!avg) {
                    avg = new Float32Array(emb.length);
                }
                for (let h = 0; h < emb.length; h++) {
                    avg[h] += emb[h];
                }
                validChunks++;
            } catch (err) {
                console.warn(`Ternlight chunk embedding failed for chunk ${i}: ${err.message}`);
            }
        }

        if (validChunks === 0) {
            throw new Error("No valid embeddings generated via Ternlight.");
        }

        for (let h = 0; h < avg.length; h++) {
            avg[h] /= validChunks;
        }

        return l2Normalize(avg);
    } catch (err) {
        console.error(`ERROR in createTernlightEmbedding: ${err.message}`);
        throw err;
    }
}
