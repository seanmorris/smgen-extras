// @ts-check

import jsdoc from 'eslint-plugin-jsdoc';

const documentedFunctions = [
  'FunctionDeclaration',
  'MethodDefinition',
  'VariableDeclarator > ArrowFunctionExpression',
];

export default [
  {
    ignores: ['node_modules/**', 'plugins/*/static/vendor/**', 'themes/*/static/vendor/**', 'themes/*/docs/**'],
  },
  {
    files: ['plugins/**/*.{js,mjs}', 'themes/*/static/*.js', 'themes/create-smgen-site/{bin,lib,tests}/**/*.js'],
    plugins: {jsdoc},
    rules: {
      'jsdoc/check-tag-names': 'error',
      'jsdoc/check-types': 'error',
      'jsdoc/require-description': ['error', {contexts: documentedFunctions}],
      'jsdoc/require-jsdoc': ['error', {
        require: {
          ArrowFunctionExpression: false,
          FunctionDeclaration: true,
          FunctionExpression: false,
          MethodDefinition: true,
        },
        contexts: ['VariableDeclarator > ArrowFunctionExpression'],
      }],
    },
  },
];
