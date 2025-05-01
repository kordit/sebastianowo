const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');

// Ścieżki do katalogów
const SRC_DIR = path.resolve(__dirname, 'src');
const DIST_DIR = path.resolve(__dirname, 'dist');

module.exports = {
    entry: {
        // Główny bundle aplikacji
        app: path.resolve(__dirname, 'src/index.js'),

        // Możesz dodać osobne entry pointy dla różnych części gry
        character: path.resolve(__dirname, 'src/modules/character/index.js'),
        inventory: path.resolve(__dirname, 'src/modules/inventory/index.js'),
        missions: path.resolve(__dirname, 'src/modules/missions/index.js'),
        areas: path.resolve(__dirname, 'src/modules/areas/index.js'),
        npc: path.resolve(__dirname, 'src/modules/npc/index.js'),
    },
    output: {
        path: DIST_DIR,
        filename: 'js/[name].bundle.js',
        clean: true, // Czyści katalog dist przed każdym buildem
    },
    devtool: 'source-map', // Dodaje source maps dla łatwiejszego debugowania
    module: {
        rules: [
            // Transpilacja JS z Babelem
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },
            // Obsługa SCSS/CSS
            {
                test: /\.(sa|sc|c)ss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'sass-loader'
                ]
            },
            // Obsługa obrazów
            {
                test: /\.(png|jpe?g|gif|svg)$/i,
                type: 'asset/resource',
                generator: {
                    filename: 'images/[name][ext]'
                }
            },
            // Obsługa fontów
            {
                test: /\.(woff|woff2|eot|ttf|otf)$/i,
                type: 'asset/resource',
                generator: {
                    filename: 'fonts/[name][ext]'
                }
            }
        ]
    },
    optimization: {
        minimize: true,
        minimizer: [
            new TerserPlugin({
                extractComments: false,
            }),
        ],
        splitChunks: {
            cacheGroups: {
                vendor: {
                    test: /[\\/]node_modules[\\/]/,
                    name: 'vendors',
                    chunks: 'all'
                }
            }
        }
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: 'css/[name].css'
        }),
    ],
    resolve: {
        extensions: ['.js', '.json'],
        alias: {
            '@': SRC_DIR,
            '@modules': path.resolve(SRC_DIR, 'modules'),
            '@utils': path.resolve(SRC_DIR, 'utils'),
            '@core': path.resolve(SRC_DIR, 'core'),
        }
    }
};
