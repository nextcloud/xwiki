const path = require('path')

const TerserPlugin = require('terser-webpack-plugin')

const appName = process.env.npm_package_name
const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'

module.exports = {
	target: 'web',
	devtool: isDev ? 'cheap-source-map' : 'source-map',
	entry: {
		main: path.resolve(path.join('src', 'main.js')),
		settings: path.resolve(path.join('src', 'settings.js'))
	},
	output: {
		path: path.resolve('./js'),
		publicPath: path.join('/apps/', appName, '/js/'),
		filename: `${appName}-[name].js?v=[contenthash]`,
		chunkFilename: `${appName}-[name].js?v=[contenthash]`,
		// Make sure sourcemaps have a proper path and do not
		// leak local paths https://github.com/webpack/webpack/issues/3603
		devtoolNamespace: appName,
		devtoolModuleFilenameTemplate(info) {
			const rootDir = process.cwd()
			const rel = path.relative(rootDir, info.absoluteResourcePath)
			return `webpack:///${appName}/${rel}`
		},
	},

	devServer: {
		hot: true,
		host: '127.0.0.1',
		port: 3000,
		client: {
			overlay: false,
		},
		devMiddleware: {
			writeToDisk: true,
		},
		headers: {
			'Access-Control-Allow-Origin': '*',
		},
	},

	optimization: {
		chunkIds: 'named',
		splitChunks: {
			automaticNameDelimiter: '-',
		},
		minimize: !isDev,
		minimizer: [
			new TerserPlugin({
				terserOptions: {
					output: {
						comments: false,
					}
				},
				extractComments: true,
			}),
		],
	},

	resolve: {
		extensions: ['*', '.js'],
		symlinks: false
	},
}
