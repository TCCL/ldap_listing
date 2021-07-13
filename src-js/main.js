// main.js

import Fuse from "fuse.js";

function delayEvent(fn,ms) {
  let id;

  function done() {
    id = null;
    fn();
  }

  function handler() {
    if (id) {
      window.clearTimeout(id);
    }

    id = window.setTimeout(done,ms);
  }

  return handler;
}

jQuery(document).ready(($) => {
  const options = {
    includeScore: true,
    keys: [
      {
        name: 'n',
        weight: 0.6
      },
      {
        name: 'j',
        weight: 0.2
      },
      {
        name: 'p',
        weight: 0.05
      },
      {
        name: 'd',
        weight: 0.15
      }
    ],
    threshold: 0.2,
    ignoreLocation: true
  };

  const manifest = JSON.parse($("#ldap-listing-directory-listing-manifest").html());

  const fuse = new Fuse(manifest,options);

  const $search = $("#ldap-listing-directory-listing-search-box");

  const handler = delayEvent(
    () => {
      const text = $search.val();
      const results = fuse.search(text);

      // TODO: Build results UI
    },
    750
  );

  $search.on("input propertychange paste",handler);
});
