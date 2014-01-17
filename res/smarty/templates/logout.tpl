<form class="mbfelogin-form" action="{$baseURL}{$action}" method="post">
    <fieldset>
        {if $error}<div class="error">{$error}</div>{/if}
        <div class="form-row clearfix">
            {assign var='logged_in_user' value=$login.feuserAuthField}
            <label for="mbfelogin_logged_in_user">{$lang.logged_in_as}</label><span id="mbfelogin_logged_in_user">{$user.$logged_in_user}</span>
        </div>
        <input type="submit" id="mbfelogin_logout" name="{$prefixId}[logout]" value="{$lang.logout}" />
    </fieldset>
</form>