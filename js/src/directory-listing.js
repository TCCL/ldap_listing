// directory-listing.js

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

  const matchesMap = {};
  for (let i = 0;i < entry.matches.length;++i) {
    const match = entry.matches[i];
    matchesMap[match.key] = match;
  }

  row.append(makeCell(entry.item.n,matchesMap["n"],entry.item.l));
  row.append(makeEmailCell(entry.item.e));
  row.append(makeCell(entry.item.j,matchesMap["j"]));
  row.append(makeCell(entry.item.p,matchesMap["p"]));
  row.append(makeCell(entry.item.d,matchesMap["d"]));

  return row;
}

function makeCell(inner,match,href) {
  const cell = jQuery("<div>").addClass("directory-listing-search-result-cell");

  let elem;
  if (href) {
    elem = jQuery("<a>").attr("href",href).attr("target","_blank");
  }
  else {
    elem = jQuery("<span>");
  }

  if (match) {
    let p = 0;
    let i = 0;
    while (p < inner.length) {
      let q;

      if (i < match.indices.length && p == match.indices[i][0]) {
        const bold = jQuery("<b>");
        q = match.indices[i][1] + 1;
        bold.append(inner.substring(p,q));
        elem.append(bold);
        i += 1;
      }
      else {
        if (i < match.indices.length) {
          q = match.indices[i][0];
        }
        else {
          q = inner.length;
        }
        elem.append(inner.substring(p,q));
      }

      p = q;
    }
  }
  else {
    elem.append(inner);
  }

  cell.append(elem);

  return cell;
}

function makeEmailCell(email) {
  const elem = jQuery("<div>").addClass("directory-listing-search-result-cell");
  const wrapper = jQuery("<div>").addClass("mail-link-wrapper");

  elem.append(wrapper);

  if (email) {
    const a = jQuery("<a>");
    const mailto = "mailto:" + email;

    a.text("✉").addClass("mail-link").attr("href",mailto).attr("title",email);
    wrapper.append(a);
  }

  return elem;
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
