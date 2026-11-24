zip -r "${PWD##*/}.zip" * -x "*/node_modules/*" -x "node_modules/*" -x "phpcs-reports/*" -x "*.gitignore" -x "*.gitmodules" -x "*.git/*" -x "*.git" -x "*.env*" -x "bin/*" -x "server/*" -x "src/*" -x "addons/*" -x "*/phpcs.xml/*" -x "*/gulpfile.js" -x "*/gulpfile.js/*" -x "*babel.config.js" -x "*Gruntfile.js" -x "*package-lock.json" -x "*/package.json/*" -x "*package.json" -x "*webpack.config.js" -x "*.xml" -x "*/phpcs-reports/*"

# Create the initial zip, excluding the zip itself and the temp folder
zip -r "${PWD##*/}.zip" * -x "*/node_modules/*" -x "node_modules/*" -x "phpcs-reports/*" -x "*.gitignore" -x "*.gitmodules" -x "*.git/*" -x "*.git" -x "*.env*" -x "bin/*" -x "server/*" -x "src/*" -x "addons/*" -x "*/phpcs.xml/*" -x "*/gulpfile.js" -x "*/gulpfile.js/*" -x "*babel.config.js" -x "*Gruntfile.js" -x "*package-lock.json" -x "*/package.json/*" -x "*package.json" -x "*webpack.config.js" -x "*.xml" -x "*/phpcs-reports/*" -x "*.zip" -x "${PWD##*/}" -x "${PWD##*/}/*" -x "*.DS_Store"

# Repackage to ensure extraction into a folder (standard practice)
unzip "${PWD##*/}".zip -d "${PWD##*/}"
rm "${PWD##*/}.zip"
zip -r "${PWD##*/}".zip "${PWD##*/}"
rm -rf "${PWD##*/}"

echo -e "\033[32mZip created successfully.\033[0m"
