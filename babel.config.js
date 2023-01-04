const babelConfig = require('@nextcloud/babel-config')

for (const preset of babelConfig.presets) {
	if (preset[0] === '@babel/preset-env') {
		preset[1].exclude = ['transform-regenerator', 'transform-async-to-generator', 'transform-for-of']
	}
}
babelConfig.presets.push("@babel/preset-typescript");
module.exports = babelConfig
