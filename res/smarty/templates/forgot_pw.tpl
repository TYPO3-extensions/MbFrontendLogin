<form class="mbfelogin-form" action="{$baseURL}{$action}" method="post">
    <fieldset>
        {if $error}<div class="error">{$error}</div>{/if}
        {if $success}
          <div class="success">{$success}</div>
        {else}
        <h1>{$lang.forgot_pw}</h1>
        <p>{$lang.desc_forgot_pw}</p>
        <div class="form-row clearfix">
            <label for="mbfelogin_user">{$lang.user}</label><input type="text" id="mbfelogin_user" name="{$prefixId}[user]" value="{if $piVars.user}{$piVars.user}{else}{$lang.user_input}{/if}" title="{$lang.user}" />
        </div>
        <input type="submit" id="mbfelogin_login" name="{$prefixId}[forgot_pw]" value="{$lang.send_password}" />
        {/if}
    </fieldset>
</form>