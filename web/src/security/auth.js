function attachJWT(http, req, tokens) {
  http.options.http._setHeaders.call(http, req, {
    "Content-Type": "application/json",
    Authorization: "Bearer " + tokens[0]
  });
}

function attachRefresh(http, req, tokens) {
  http.options.http._setData.call(http, req, { refresh_token: tokens[1] });
}

export default {
  request: function(req, token) {
    let isRefresh = req.url.indexOf("refresh") > -1;
    let isLogout = req.url.indexOf("logout") > -1;

    let tokens = token.split(";");

    if (isRefresh) {
      attachRefresh(this, req, tokens);
    } else if (isLogout) {
      attachJWT(this, req, tokens);
      attachRefresh(this, req, tokens);
    } else {
      attachJWT(this, req, tokens);
    }
  },

  response: function(res) {
    if (
      res.data.hasOwnProperty("token") &&
      res.data.hasOwnProperty("refresh_token")
    )
      return Object.values(res.data).join(";");
  }
};