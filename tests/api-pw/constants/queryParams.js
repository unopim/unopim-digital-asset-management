

function criteria(column, value, operator = '=') {
  return { [column]: [{ operator, value }] };
}

function filter(column, value, operator = '=') {
  return { filters: JSON.stringify(criteria(column, value, operator)) };
}

function filters(...fragments) {
  return { filters: JSON.stringify(Object.assign({}, ...fragments)) };
}

function paginate(page = 1, limit = 10) {
  return { page, limit };
}

function sort(column = 'id', order = 'desc') {
  return {
    'sort[column]': column,
    'sort[order]': order,
  };
}

function searchByName(value, operator = 'LIKE') {
  return filter('code', value, operator);
}

module.exports = { criteria, filter, filters, paginate, sort, searchByName };
