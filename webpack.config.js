const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    entry: './assets/src/index.js',
    output: {
      path: path.resolve(__dirname, 'assets/js'),
      filename: 'admin-script.js',
    },
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env', '@babel/preset-react'],
            },
          },
        },
        {
          test: /\.(css|scss)$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            'sass-loader',
          ],
        },
      ],
    },
    resolve: {
      extensions: ['.js', '.jsx'],
    },
    plugins: [
      new MiniCssExtractPlugin({
        filename: '../css/admin-style.css',
      }),
    ],
    devtool: isProduction ? false : 'source-map',
    externals: {
      '@wordpress/api-fetch': 'wp.apiFetch',
      '@wordpress/i18n': 'wp.i18n',
    },
  };
};
