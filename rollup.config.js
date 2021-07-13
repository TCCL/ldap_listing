// rollup.config.js - ldap_listing

import resolve from '@rollup/plugin-node-resolve';

export default {
  input: 'src-js/main.js',
  output: {
    file: 'js/directory-listing.js',
    format: 'iife'
  },
  plugins: [
    resolve()
  ]
};
