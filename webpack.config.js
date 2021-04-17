'use strict';

const Encore = require('@symfony/webpack-encore');
const fs = require('fs');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .addEntry('main', './assets/js/main.js')
    .addExternals({
        'bazinga-translator': 'Translator',
        'fosjsrouting': 'Routing',
    })
    .addAliases({
        'jquery$': 'jquery/dist/jquery.slim',
    })
    .cleanupOutputBeforeBuild()
    .copyFiles({
        from: './assets/icons',
        to: 'images/[path][name].[hash:8].[ext]',
        pattern: /\.svg$/i,
    })
    .enableLessLoader()
    .enablePostCssLoader()
    .enableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning()
    .enableIntegrityHashes()
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .splitEntryChunks();

(function addStyleEntrypoints(directory, prefix) {
    fs.readdirSync(directory)
        .filter(name => !name.startsWith('_'))
        .map(name => ({ name, stat: fs.statSync(`${directory}/${name}`) }))
        .map(file => ({
            name: file.name,
            isFile: () => file.stat.isFile(),
            isDirectory: () => file.stat.isDirectory(),
        }))
        .forEach(file => {
            const filePath = `${directory}/${file.name}`;

            if (file.isFile() && prefix && /^index\.(le|c)ss$/i.test(file.name)) {
                Encore.addStyleEntry(prefix.replace(/\/$/, ''), filePath);
            } else if (file.isFile() && /\.(le|c)ss$/i.test(file.name)) {
                const entryName = file.name.replace(/\..+?$/, '');

                Encore.addStyleEntry(prefix + entryName, filePath);
            } else if (file.isDirectory()) {
                const newPrefix = prefix + file.name + '/';

                addStyleEntrypoints(filePath, newPrefix);
            }
        });
})(__dirname + '/assets/css', '');

module.exports = Encore.getWebpackConfig();
