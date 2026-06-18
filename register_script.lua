wrk.method = "POST"
wrk.headers["Content-Type"] = "application/json"
wrk.headers["Accept"] = "application/json"

request = function()
  local phone = tostring(math.random(100000000, 999999999))
  local body = '{"first_name":"Test","last_name":"User","phone":"' .. phone .. '","password":"password","location":"Gaza","fcm_token":"test_token"}'
  return wrk.format(nil, "/api/auth/register", nil, body)
end
