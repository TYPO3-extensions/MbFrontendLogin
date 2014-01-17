<html>
  <head>
    <title>{$lang.forgot_pw_mail_headline}</title>
    <style>
      {literal}
      html,
      body,
      * {
        font-family: Arial, sans-serif;
        font-weight: normal;
        font-size: 11px;
        line-height: 15px;
      }
      h1 {
        font-size: 15px;
        line-height: 19px;
      }
      h2 {
        font-size: 13px;
        line-height: 17px;
      }
      {/literal}
    </style>
  </head>
  <body>
    <h1>{$lang.forgot_pw_mail_headline}</h1>
    <h2>{$baseURL}</h2>
    <p>{$lang.forgot_pw_mail_new_password} {$password}</p>
  </body>
</html>