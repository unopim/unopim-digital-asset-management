/**
 * HTTP status codes the DAM API is expected to return, named for readability
 * in assertions: `expect(res.status).toBe(STATUS.CREATED)`.
 */
const STATUS = {
  OK:                    200,
  CREATED:               201,
  ACCEPTED:              202, // directory delete is queued → 202
  NO_CONTENT:            204,
  BAD_REQUEST:           400,
  UNAUTHORIZED:          401,
  FORBIDDEN:             403,
  NOT_FOUND:             404,
  CONFLICT:              409,
  UNPROCESSABLE_ENTITY:  422, // Laravel validation failures
  SERVER_ERROR:          500,
};

module.exports = { STATUS };
