<!DOCTYPE html>
<html lang="fr" dir="ltr">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Exporter: symfony-api_auth - database_auth - Adminer</title>
<link rel="stylesheet" href="?file=default.css&amp;version=5.3.0">
<link rel='stylesheet' media='(prefers-color-scheme: dark)' href='?file=dark.css&amp;version=5.3.0'>
<meta name='color-scheme' content='light dark'>
<script src='?file=functions.js&amp;version=5.3.0' nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI="></script>
<link rel='icon' href='data:image/gif;base64,R0lGODlhEAAQAJEAAAQCBPz+/PwCBAROZCH5BAEAAAAALAAAAAAQABAAAAI2hI+pGO1rmghihiUdvUBnZ3XBQA7f05mOak1RWXrNq5nQWHMKvuoJ37BhVEEfYxQzHjWQ5qIAADs='>
<link rel='apple-touch-icon' href='?file=logo.png&amp;version=5.3.0'>

<body class='ltr nojs adminer'>
<script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">mixin(document.body, {onkeydown: bodyKeydown, onclick: bodyClick});
document.body.classList.replace('nojs', 'js');
const offlineMessage = 'Vous êtes hors ligne.';
const thousandsSeparator = ',';</script>
<div id='help' class='jush-sql jsonly hidden'></div>
<script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">mixin(qs('#help'), {onmouseover: () => { helpOpen = 1; }, onmouseout: helpMouseout});</script>
<div id='content'>
<span id='menuopen' class='jsonly'><button type='submit' name='' title='' class='icon icon-move'><span>menu</span></button></span><script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">qs('#menuopen').onclick = event => { qs('#foot').classList.toggle('foot'); event.stopPropagation(); }</script>
<p id="breadcrumb"><a href="?server=database_auth">MariaDB</a> » <a href='?server=database_auth&amp;username=user' accesskey='1' title='Alt+Shift+1'>database_auth</a> » <a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth">symfony-api_auth</a> » Exporter
<h2>Exporter: symfony-api_auth</h2>
<div id='ajaxstatus' class='jsonly hidden'></div>

<form action="" method="post">
<table class="layout">
<tr><th>Sortie<td><label><input type='radio' name='output' value='text' checked>ouvrir</label><label><input type='radio' name='output' value='file'>enregistrer</label><label><input type='radio' name='output' value='gz'>gzip</label>
<tr><th>Format<td><label><input type='radio' name='format' value='sql' checked>SQL</label><label><input type='radio' name='format' value='csv'>CSV,</label><label><input type='radio' name='format' value='csv;'>CSV;</label><label><input type='radio' name='format' value='tsv'>TSV</label>
<tr><th>Base de données<td><select name='db_style'><option selected><option>USE<option>DROP+CREATE<option>CREATE</select><label><input type='checkbox' name='routines' value='1' checked>Routines</label><label><input type='checkbox' name='events' value='1' checked>Évènements</label><tr><th>Tables<td><select name='table_style'><option><option selected>DROP+CREATE<option>CREATE</select><label><input type='checkbox' name='auto_increment' value='1'>Incrément automatique</label><label><input type='checkbox' name='triggers' value='1' checked>Déclencheurs</label><tr><th>Données<td><select name='data_style'><option><option>TRUNCATE+INSERT<option selected>INSERT<option>INSERT+UPDATE</select></table>
<p><input type="submit" value="Exporter">
<input type='hidden' name='token' value='1028676:415429'>

<table>
<script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">qsl('table').onclick = dumpClick;</script>
<thead><tr><th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables' checked>Tables</label><script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">qs('#check-tables').onclick = partial(formCheck, /^tables\[/);</script><th style='text-align: right;'><label class='block'>Données<input type='checkbox' id='check-data' checked></label><script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">qs('#check-data').onclick = partial(formCheck, /^data\[/);</script></thead>
<tr><td><label class='block'><input type='checkbox' name='tables[]' value='auth_api_client' checked>auth_api_client</label><td align='right'><label class='block'><span id='Rows-auth_api_client'></span><input type='checkbox' name='data[]' value='auth_api_client' checked></label>
<tr><td><label class='block'><input type='checkbox' name='tables[]' value='auth_api_client_scope' checked>auth_api_client_scope</label><td align='right'><label class='block'><span id='Rows-auth_api_client_scope'></span><input type='checkbox' name='data[]' value='auth_api_client_scope' checked></label>
<tr><td><label class='block'><input type='checkbox' name='tables[]' value='auth_api_client_secret' checked>auth_api_client_secret</label><td align='right'><label class='block'><span id='Rows-auth_api_client_secret'></span><input type='checkbox' name='data[]' value='auth_api_client_secret' checked></label>
<tr><td><label class='block'><input type='checkbox' name='tables[]' value='auth_api_request' checked>auth_api_request</label><td align='right'><label class='block'><span id='Rows-auth_api_request'></span><input type='checkbox' name='data[]' value='auth_api_request' checked></label>
<tr><td><label class='block'><input type='checkbox' name='tables[]' value='doctrine_migration_versions' checked>doctrine_migration_versions</label><td align='right'><label class='block'><span id='Rows-doctrine_migration_versions'></span><input type='checkbox' name='data[]' value='doctrine_migration_versions' checked></label>
<script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">ajaxSetHtml('?server=database_auth&username=user&db=symfony-api_auth&script=db');</script>
</table>
</form>
<p><a href='?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;dump=auth%25'>auth</a></div>

<div id='foot' class='foot'>
<div id='menu'>
<h1><a href='https://www.adminer.org/' target="_blank" rel="noreferrer noopener" id='h1'><img src='?file=logo.png&amp;version=5.3.0' width='24' height='24' alt='' id='logo'>Adminer</a> <span class='version'>5.3.0 <a href='https://www.adminer.org/#download' target="_blank" rel="noreferrer noopener" id='version'>5.5.1</a></span></h1>
<form action='' method='post'>
<div id='lang'><label>Langue: <select name='lang'><option value="en">English<option value="ar">العربية<option value="bg">Български<option value="bn">বাংলা<option value="bs">Bosanski<option value="ca">Català<option value="cs">Čeština<option value="da">Dansk<option value="de">Deutsch<option value="el">Ελληνικά<option value="es">Español<option value="et">Eesti<option value="fa">فارسی<option value="fi">Suomi<option value="fr" selected>Français<option value="gl">Galego<option value="he">עברית<option value="hi">हिन्दी<option value="hu">Magyar<option value="id">Bahasa Indonesia<option value="it">Italiano<option value="ja">日本語<option value="ka">ქართული<option value="ko">한국어<option value="lt">Lietuvių<option value="lv">Latviešu<option value="ms">Bahasa Melayu<option value="nl">Nederlands<option value="no">Norsk<option value="pl">Polski<option value="pt">Português<option value="pt-br">Português (Brazil)<option value="ro">Limba Română<option value="ru">Русский<option value="sk">Slovenčina<option value="sl">Slovenski<option value="sr">Српски<option value="sv">Svenska<option value="ta">த‌மிழ்<option value="th">ภาษาไทย<option value="tr">Türkçe<option value="uk">Українська<option value="uz">Oʻzbekcha<option value="vi">Tiếng Việt<option value="zh">简体中文<option value="zh-tw">繁體中文</select><script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">qsl('select').onchange = function () { this.form.submit(); };</script></label> <input type='submit' value='Utiliser' class='hidden'>
<input type='hidden' name='token' value='864553:316840'>
</div>
</form>
<script src='?file=jush.js&amp;version=5.3.0' nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=" defer></script>
<script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">
var jushLinks = { sql: [ '?server=database_auth&username=user&db=symfony-api_auth&table=$&', /\b(auth_api_client|auth_api_client_scope|auth_api_client_secret|auth_api_request|doctrine_migration_versions)\b/g ] };
jushLinks.bac = jushLinks.sql;
jushLinks.bra = jushLinks.sql;
jushLinks.sqlite_quo = jushLinks.sql;
jushLinks.mssql_bra = jushLinks.sql;
</script>
<script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">syntaxHighlighting('11', 'maria');</script>
<form action=''>
<p id='dbs'>
<input type='hidden' name='server' value='database_auth'>
<input type='hidden' name='username' value='user'>
<label title='Base de données'>BD: <select name='db'><option value=""><option>information_schema<option selected>symfony-api_auth</select><script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">mixin(qsl('select'), {onmousedown: dbMouseDown, onchange: dbChange});</script>
</label><input type='submit' value='Utiliser' class='hidden'>
<input type='hidden' name='dump' value=''>
</p></form>
<p class='links'>
<a href='?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;sql='>Requête SQL</a>
<a href='?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;import='>Importer</a>
<a href='?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;dump=' id='dump' class='active '>Exporter</a>
<a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;create=">Créer une table</a>
<ul id='tables'><script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">mixin(qs('#tables'), {onmouseover: menuOver, onmouseout: menuOut});</script>
<li><a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;select=auth_api_client" class='select' title='Afficher les données'>select</a> <a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;table=auth_api_client" class='structure' title='Afficher la structure'>auth_api_client</a>
<li><a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;select=auth_api_client_scope" class='select' title='Afficher les données'>select</a> <a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;table=auth_api_client_scope" class='structure' title='Afficher la structure'>auth_api_client_scope</a>
<li><a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;select=auth_api_client_secret" class='select' title='Afficher les données'>select</a> <a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;table=auth_api_client_secret" class='structure' title='Afficher la structure'>auth_api_client_secret</a>
<li><a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;select=auth_api_request" class='select' title='Afficher les données'>select</a> <a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;table=auth_api_request" class='structure' title='Afficher la structure'>auth_api_request</a>
<li><a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;select=doctrine_migration_versions" class='select' title='Afficher les données'>select</a> <a href="?server=database_auth&amp;username=user&amp;db=symfony-api_auth&amp;table=doctrine_migration_versions" class='structure' title='Afficher la structure'>doctrine_migration_versions</a>
</ul>
</div>
<form action="" method="post">
<p class="logout">
<span>user
</span>
<input type="submit" name="logout" value="Déconnexion" id="logout">
<input type='hidden' name='token' value='189052:722685'>
</form>
</div>

<script nonce="MWM0NTllODRiYzRjZTc0ZjA5M2Y1NTcxNGFjOTY2ZDI=">setupSubmitHighlight(document);</script>
