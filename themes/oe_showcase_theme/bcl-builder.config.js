const path = require("path");

const outputFolder = path.resolve(__dirname);
const nodeModules = path.resolve(__dirname, "./node_modules");

// SCSS includePaths
const includePaths = [nodeModules];

module.exports = {
  colorScheme: [
    {
      entry: path.resolve(outputFolder, "resources/sass/color-scheme-variables.scss"),
      dest: path.resolve(outputFolder, "assets/css/color_scheme.min.css"),
      options: {
        includePaths,
        minify: true,
        sourceMap: "file",
      },
    },
  ],
  styles: [
    {
      entry: path.resolve(outputFolder, "resources/sass/oe_showcase_theme.scss"),
      dest: path.resolve(outputFolder, "assets/css/oe_showcase_theme.min.css"),
      options: {
        includePaths,
        minify: true,
        sourceMap: "file",
      },
    },
  ]
};
