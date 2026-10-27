// @ts-check

import jsdoc from 'eslint-plugin-jsdoc';

const documentedFunctions = [
  'FunctionDeclaration',
  'MethodDefinition',
  'VariableDeclarator > ArrowFunctionExpression',
];

export default [
  {
    ignores: ['node_modules/**'],
  },
  {
    files: ['packages/**/*.js'],
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
