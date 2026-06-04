/**
 * Query-string builders for the asset list endpoint.
 *
 * The DAM asset index extends UnoPim's AdminApi `ApiDataSource`, which reads
 * filters / pagination / sort from the query string in a bracketed convention.
 * That convention lives here in ONE place: if your AdminApi version expects
 * different keys, adjust these builders and every list/search/filter/sort spec
 * follows. Builders return flat `{ "key[0][value]": "x" }` maps consumable by
 * the ApiClient's query serialiser.
 *
 * Columns supported by `AssetDataSource::operatorByFilter`:
 *   file_type, mime_type, extension, file_size, created_at, updated_at, code
 */

/** `filters[column][0][operator]=op & filters[column][0][value]=value` */
function filter(column, value, operator = '=') {
  return {
    [`filters[${column}][0][operator]`]: operator,
    [`filters[${column}][0][value]`]: value,
  };
}

/** Merge multiple filter() results into one combined query map. */
function filters(...specs) {
  return Object.assign({}, ...specs);
}

/** `pagination[page]` / `pagination[limit]` */
function paginate(page = 1, limit = 10) {
  return {
    'pagination[page]': page,
    'pagination[limit]': limit,
  };
}

/** `sort[column]` / `sort[order]` (order: asc | desc) */
function sort(column = 'id', order = 'desc') {
  return {
    'sort[column]': column,
    'sort[order]': order,
  };
}

/** Free-text name search via the `code` filter the datasource exposes. */
function searchByName(value, operator = 'LIKE') {
  return filter('code', value, operator);
}

module.exports = { filter, filters, paginate, sort, searchByName };
