{if $security.enableFrontendMD5 == 'true'} <script src="{$baseURL}{$extPath}res/js/md5.js"></script>{/if}
<form class="mbfelogin-form" action="{$baseURL}{$action}" method="post" {if $security.feuserPasswordIsASaltedPassword != 'true' && $security.enableFrontendMD5 == 'true'}onSubmit="document.getElementById('mbfelogin_password').value = MD5(document.getElementById('mbfelogin_password').value);"{/if}{if $security.feuserPasswordIsASaltedPassword == 'true' || $security.feuserPasswordIsMD5 == 'false'}onSubmit="document.getElementById('mbfelogin_password').value = '{$security.passwordSalt}'+document.getElementById('mbfelogin_password').value;"{/if}>
    <fieldset>
        {if $error}<div class="error">{$error}</div>{/if}
        <div class="form-row clearfix">
            <label for="mbfelogin_user">{$lang.user}</label><input type="text" id="mbfelogin_user" name="{$prefixId}[user]" value="{if $piVars.user}{$piVars.user}{else}{$lang.user_input}{/if}" title="{$lang.user}" />
        </div>
        <div class="form-row clearfix">
            <label for="mbfelogin_password">{$lang.password}</label><input type="password" id="mbfelogin_password" name="{$prefixId}[password]" value="{if $piVars.password}{else}{$lang.password_input}{/if}" title="{$lang.password}" />
        </div>
        <input type="submit" id="mbfelogin_login" name="{$prefixId}[login]" value="{$lang.login}" />
        {if $login.showForgotPasswordLink == 'true' && $forgot_pw_link}
        <div class="forgot_pw clearfix">
          <a href="{$forgot_pw_link}">{$lang.forgot_pw}</a>
        </div>
        {/if}
    </fieldset>
</form>