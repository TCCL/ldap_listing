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

function makeRow(entry) {
  const row = jQuery("<div>")
        .addClass("directory-listing-search-result-row");

  row.append(makeCell(entry.item.n));
  row.append(makeCell(entry.item.j));
  row.append(makeCell(entry.item.p));
  row.append(makeCell(entry.item.d));

  return row;
}

function makeCell(inner) {
  const cell = jQuery("<div>")
        .addClass("directory-listing-search-result-cell");

  cell.append(inner);

  return cell;
}

jQuery(document).ready(($) => {
  const options = {
    includeScore: true,
    includeMatches: true,
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

  const $manifest = $("#ldap-listing-directory-listing-manifest");
  const manifest = JSON.parse($manifest.html());
  $manifest.remove();

  const fuse = new Fuse(manifest,options);

  const $results = $("#ldap-listing-directory-listing-results-region");
  const $search = $("#ldap-listing-directory-listing-search-box");

  const handler = delayEvent(
    () => {
      $results.empty();

      const text = $search.val();
      if (text == "") {
        return;
      }

      const results = fuse.search(text);
      for (let i = 0;i < results.length;++i) {
        const entry = results[i];
        const row = makeRow(entry);

        $results.append(row);
      }
    },
    750
  );

  $search.on("input propertychange paste",handler);
});
