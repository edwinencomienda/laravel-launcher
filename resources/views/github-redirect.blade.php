<form action="https://github.com/settings/apps/new" method="post">
    Register a GitHub App Manifest: <input type="text" name="manifest" id="manifest"
        value="{{ json_encode($manifest) }}"><br>
    <input type="submit" value="Submit">
</form>
