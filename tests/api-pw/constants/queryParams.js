/**
 * Query-string builders for the asset list endpoint.
 *
 * The DAM asset index extends UnoPim's AdminApi `ApiDataSource`, which reads
 * `filters`, `sort`, `limit` and `page` from the query string. Filters use a
 * single JSON-encoded `filters` parameter — NOT bracketed keys:
 *
 *   filters={"file_type":[{"operator":"=","value":"image"}]}
 *
 * (see `QueryParametersChecker::checkCriterionParameters`, which json_decodes
 * the value). Pagination is flat `limit` + `page`. This convention lives here
 * in ONE place so every list/search/filter/sort spec follows.
 *
 * Columns the list endpoint filters on (registered in
 * `AssetDataSource::prepareApiQueryBuilder`):
 *   file_type, mime_type, extension, file_size, file_name, code,
 *   created_at, updated_at
 */

/**
 * A single filter criterion fragment: `{ column: [{ operator, value }] }`.
 * Combine several with {@link filters} to filter on multiple columns at once.
 */
function criteria(column, value, operator = '=') {
  return { [column]: [{ operator, value }] };
}

/** One-column filter → `{ filters: '<json>' }`, ready to pass as query params. */
function filter(column, value, operator = '=') {
  return { filters: JSON.stringify(criteria(column, value, operator)) };
}

/** Combine multiple {@link criteria} fragments into one `filters` param. */
function filters(...fragments) {
  return { filters: JSON.stringify(Object.assign({}, ...fragments)) };
}

/** Flat `limit` / `page` — the keys the AdminApi paginator actually reads. */
function paginate(page = 1, limit = 10) {
  return { page, limit };
}

/** `sort[column]` / `sort[order]` (order: asc | desc). */
function sort(column = 'id', order = 'desc') {
  return {
    'sort[column]': column,
    'sort[order]': order,
  };
}

/** Free-text name search via the `code` filter (mapped to file_name server-side). */
function searchByName(value, operator = 'LIKE') {
  return filter('code', value, operator);
}

module.exports = { criteria, filter, filters, paginate, sort, searchByName };
