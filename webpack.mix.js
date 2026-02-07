const mix = require('laravel-mix');
const exec = require('child_process').exec;
require('dotenv').config();

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

const glob = require('glob');
const path = require('path');

/*
 |--------------------------------------------------------------------------
 | Vendor assets
 |--------------------------------------------------------------------------
 */

function mixAssetsDir(query, cb) {
    (glob.sync('resources/' + query) || []).forEach((f) => {
        f = f.replace(/[\\\/]+/g, '/');
        cb(f, f.replace('resources', 'public'));
    });
}

const sassOptions = {
    precision: 5,
    includePaths: ['node_modules', 'resources/assets/'],
};

// plugins Core stylesheets
mixAssetsDir('sass/keyinstitute/base/plugins/**/!(_)*.scss', (src, dest) =>
    mix.sass(
        src,
        dest
            .replace(/(\\|\/)sass(\\|\/)/, '$1css$2')
            .replace(/\.scss$/, '.css'),
        { sassOptions }
    )
);

// pages Core stylesheets
mixAssetsDir('sass/keyinstitute/base/pages/**/!(_)*.scss', (src, dest) =>
    mix.sass(
        src,
        dest
            .replace(/(\\|\/)sass(\\|\/)/, '$1css$2')
            .replace(/\.scss$/, '.css'),
        { sassOptions }
    )
);

// Core stylesheets
mixAssetsDir('sass/keyinstitute/base/core/**/!(_)*.scss', (src, dest) =>
    mix.sass(
        src,
        dest
            .replace(/(\\|\/)sass(\\|\/)/, '$1css$2')
            .replace(/\.scss$/, '.css'),
        { sassOptions }
    )
); // plugins Core stylesheets
mixAssetsDir('sass/knowledgespace/base/plugins/**/!(_)*.scss', (src, dest) =>
    mix.sass(
        src,
        dest
            .replace(/(\\|\/)sass(\\|\/)/, '$1css$2')
            .replace(/\.scss$/, '.css'),
        { sassOptions }
    )
);

// pages Core stylesheets
mixAssetsDir('sass/knowledgespace/base/pages/**/!(_)*.scss', (src, dest) =>
    mix.sass(
        src,
        dest
            .replace(/(\\|\/)sass(\\|\/)/, '$1css$2')
            .replace(/\.scss$/, '.css'),
        { sassOptions }
    )
);

// Core stylesheets
mixAssetsDir('sass/knowledgespace/base/core/**/!(_)*.scss', (src, dest) =>
    mix.sass(
        src,
        dest
            .replace(/(\\|\/)sass(\\|\/)/, '$1css$2')
            .replace(/\.scss$/, '.css'),
        { sassOptions }
    )
);

// script js
mixAssetsDir('js/scripts/**/*.js', (src, dest) => mix.scripts(src, dest));

/*
 |--------------------------------------------------------------------------
 | Application assets
 |--------------------------------------------------------------------------
 */

mixAssetsDir('vendors/js/**/*.js', (src, dest) => mix.scripts(src, dest));
mixAssetsDir('vendors/css/**/*.css', (src, dest) => mix.copy(src, dest));
mixAssetsDir('vendors/**/**/images', (src, dest) => mix.copy(src, dest));
mixAssetsDir('vendors/css/editors/quill/fonts/', (src, dest) =>
    mix.copy(src, dest)
);
mixAssetsDir('vendors/vendor/', (src, dest) => mix.copy(src, dest));
mixAssetsDir('fonts', (src, dest) => mix.copy(src, dest));
mixAssetsDir('fonts/**/**/*.css', (src, dest) => mix.copy(src, dest));
mix.copyDirectory('resources/images', 'public/images');
mix.copyDirectory('resources/data', 'public/data');

mix.js('resources/js/core/app-menu.js', 'public/js/core')
    .js('resources/js/core/app.js', 'public/js/core')
    .js('resources/js/scripts/_my/tinymce-init.js', 'public/js/core')
    .js('resources/assets/js/scripts.js', 'public/js/core')
    .sass('resources/sass/keyinstitute/core.scss', 'public/css/keyinstitute', {
        sassOptions,
    })
    .sass(
        'resources/sass/keyinstitute/overrides.scss',
        'public/css/keyinstitute',
        { sassOptions }
    )
    .sass(
        'resources/sass/keyinstitute/base/components.scss',
        'public/css/keyinstitute',
        { sassOptions }
    )
    .sass(
        'resources/sass/keyinstitute/base/custom-rtl.scss',
        'public/css-rtl/keyinstitute',
        { sassOptions }
    )
    .sass(
        'resources/assets/scss/keyinstitute/style-rtl.scss',
        'public/css-rtl/keyinstitute',
        { sassOptions }
    )
    .sass(
        'resources/assets/scss/keyinstitute/style.scss',
        'public/css/keyinstitute',
        { sassOptions }
    )
    .sass('resources/assets/scss/common-style.scss', 'public/css', {
        sassOptions,
    })
    .sass(
        'resources/assets/scss/keyinstitute/default.scss',
        'public/css/keyinstitute',
        { sassOptions }
    )
    .sass(
        'resources/sass/knowledgespace/core.scss',
        'public/css/knowledgespace',
        { sassOptions }
    )
    .sass(
        'resources/sass/knowledgespace/overrides.scss',
        'public/css/knowledgespace',
        { sassOptions }
    )
    .sass(
        'resources/sass/knowledgespace/base/components.scss',
        'public/css/knowledgespace',
        { sassOptions }
    )
    .sass(
        'resources/sass/knowledgespace/base/custom-rtl.scss',
        'public/css-rtl/knowledgespace',
        { sassOptions }
    )
    .sass(
        'resources/assets/scss/knowledgespace/style-rtl.scss',
        'public/css-rtl/knowledgespace',
        { sassOptions }
    )
    .sass(
        'resources/assets/scss/knowledgespace/style.scss',
        'public/css/knowledgespace',
        { sassOptions }
    )
    .sass(
        'resources/assets/scss/knowledgespace/default.scss',
        'public/css/knowledgespace',
        { sassOptions }
    );

mix.then(() => {
    if (process.env.MIX_CONTENT_DIRECTION === 'rtl') {
        let command = `node ${path.resolve(
            'node_modules/rtlcss/bin/rtlcss.js'
        )} -d -e ".css" ./public/css/ ./public/css/`;
        exec(command, function (err, stdout, stderr) {
            if (err !== null) {
                console.log(err);
            }
        });
    }
});
// mix.vue();

mix.disableNotifications();

if (mix.inProduction()) {
    mix.version();
    //   mix.webpackConfig({
    //     output: {
    //       publicPath: '/demo/vuexy-bootstrap-laravel-admin-template-new/demo-2/'
    //     }
    //   })
    //   mix.setResourceRoot('/demo/vuexy-bootstrap-laravel-admin-template-new/demo-2/')
}
