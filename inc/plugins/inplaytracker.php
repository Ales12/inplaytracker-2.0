<?php

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}


/* Hooks*/

// Alerts
if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
    $plugins->add_hook("global_start", "inplaytracker_alerts");
}
//Newthread Hooks
$plugins->add_hook("newthread_start", "inplaytracker_newscene");
$plugins->add_hook("newthread_do_newthread_end", "inplaytracker_newscene_do");

// Thread editieren
$plugins->add_hook("editpost_end", "inplaytracker_editscene");
$plugins->add_hook("editpost_do_editpost_end", "inplaytracker_editscene_do");

// Antworten
$plugins->add_hook("newreply_do_newreply_end", "inplaytracker_reply_do");

// showthreads 
$plugins->add_hook('showthread_start', 'inplaytracker_showthread');

// forumdisplay
$plugins->add_hook('forumdisplay_thread_end', 'inplaytracker_forumdisplay');

// Misc
$plugins->add_hook('misc_start', 'inplaytracker_misc');

// Global
$plugins->add_hook('global_start', 'inplaytracker_global');

// Profil
$plugins->add_hook('member_profile_end', 'inplaytracker_member_profile');

//wer ist wo
$plugins->add_hook('fetch_wol_activity_end', 'inplaytracker_user_activity');
$plugins->add_hook('build_friendly_wol_location_end', 'inplaytracker_location_activity');

function inplaytracker_info()
{
    return array(
        "name" => "Inplaytracker",
        "description" => "Szenenübersicht deiner Charaktere. Szeneninformation im Profil. Szenenerinnerungen, wenn länger nicht mehr gepostet wurde.",
        "website" => "https://github.com/Ales12/inplaytracker-2.0",
        "author" => "Ales",
        "authorsite" => "https://github.com/Ales12",
        "version" => "2.0",
        "guid" => "",
        "codename" => "",
        "compatibility" => "*"
    );
}

function inplaytracker_install()
{
    global $db, $cache;
    //Datenbank erstellen
    if ($db->engine == 'mysql' || $db->engine == 'mysqli') {
        $db->query("ALTER TABLE `" . TABLE_PREFIX . "threads` ADD `charas` varchar(400) CHARACTER SET utf8 NOT NULL;");
        $db->query("ALTER TABLE `" . TABLE_PREFIX . "threads` ADD `date` varchar(400)  NOT NULL;");
        $db->query("ALTER TABLE `" . TABLE_PREFIX . "threads` ADD `time` varchar(400) NOT NULL;");
        $db->query("ALTER TABLE `" . TABLE_PREFIX . "threads` ADD `place` varchar(400) CHARACTER SET utf8 NOT NULL ;");
        $db->query("ALTER TABLE `" . TABLE_PREFIX . "threads` ADD `add_charas` int(10) DEFAULT 0;");
    }

    // Einstellungen
    $setting_group = array(
        'name' => 'inplaytracker',
        'title' => 'Inplayszenen Einstellungen',
        'description' => 'Einstellungen für Inplayszenenübersicht und Scenesreminder',
        'disporder' => 2,
        'isdefault' => 0
    );

    $gid = $db->insert_query("settinggroups", $setting_group);


    $setting_array = array(
        'name' => 'ipt_inplay_id',
        'title' => 'Kategorien ID',
        'description' => 'Gib hier die ID deiner Inplaykategorie an.',
        'optionscode' => 'forumselectsingle ',
        'value' => '1',
        'disporder' => 1,
        "gid" => (int) $gid
    );
    $db->insert_query('settings', $setting_array);

    $setting_array = array(
        'name' => 'ipt_archive_id',
        'title' => 'Archiv ID',
        'description' => 'Gib hier die ID deines Archivs an.',
        'optionscode' => 'forumselectsingle ',
        'value' => '2',
        'disporder' => 2,
        "gid" => (int) $gid
    );
    $db->insert_query('settings', $setting_array);

    $setting_array = array(
        'name' => 'ipt_scenereminder',
        'title' => 'Scenenerinnerung',
        'description' => 'Ab wie vielen Tagen soll eine Erinnerung für eine Szene erscheinen?',
        'optionscode' => 'numeric',
        'value' => '60',
        'disporder' => 3,
        "gid" => (int) $gid
    );
    $db->insert_query('settings', $setting_array);

    $setting_array = array(
        'name' => 'ipt_messager',
        'title' => 'Ist der Messagerplugin installiert?',
        'description' => 'Ab wie vielen Tagen soll eine Erinnerung für eine Szene erscheinen?',
        'optionscode' => 'yesno',
        'value' => '0',
        'disporder' => 4,
        "gid" => (int) $gid
    );
    $db->insert_query('settings', $setting_array);

    $setting_array = array(
        'name' => 'ipt_messager_id',
        'title' => 'Messagerforum',
        'description' => 'In welchem Forum liegt der Messager?',
        'optionscode' => 'forumselectsingle ',
        'value' => '2',
        'disporder' => 5,
        "gid" => (int) $gid
    );
    $db->insert_query('settings', $setting_array);


    // Templates
    $insert_array = array(
        'title' => 'ipt_editscene',
        'template' => $db->escape_string('<tr>
	<td class="tcat" colspan="2"><strong>{$lang->ipt_editscene}</strong></td>
</tr>
<tr>
	<tr>
		<td class="trow1"><strong>{$lang->ipt_charas}</strong></td>
		<td class="trow1"><input type="text" class="textbox" name="charas" id="charas" size="40" maxlength="1155" value="{$charas}" style="min-width: 347px; max-width: 100%;" /> </td>
</tr>
<tr>
		<td class="trow2"><strong>{$lang->ipt_date}</strong></td>	
		<td class="trow2"><input type="date" class="textbox" name="date" id="date" size="40" maxlength="1155" value="{$date}" /> <input type="text" class="textbox" name="time" id="time"  size="40" maxlength="1155" value="{$time}" /> </td>
</tr>		
<tr>
		<td class="trow2"><strong>{$lang->ipt_place}</strong></td>	
		<td class="trow2"><input type="text" class="textbox" name="place" id="place" size="40" maxlength="1155" value="{$place}"></td>
</tr>
		<tr>
		<td class="trow1"><strong>{$lang->ipt_scenekind}</strong></td>
		<td class="trow1"><select name="add_charas">
			{$scenestatus}
			</select></td>
</tr>

<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
<script type="text/javascript">
<!--
if(use_xmlhttprequest == "1")
{
    MyBB.select2();
    $("#charas").select2({
        placeholder: "{$lang->search_user}",
        minimumInputLength: 2,
        maximumSelectionSize: \'\',
        multiple: true,
        ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
            url: "xmlhttp.php?action=get_users",
            dataType: \'json\',
            data: function (term, page) {
                return {
                    query: term, // search term
                };
            },
            results: function (data, page) { // parse the results into the format expected by Select2.
                // since we are using custom formatting functions we do not need to alter remote JSON data
                return {results: data};
            }
        },
        initSelection: function(element, callback) {
            var query = $(element).val();
            if (query !== "") {
                var newqueries = [];
                exp_queries = query.split(",");
                $.each(exp_queries, function(index, value ){
                    if(value.replace(/\s/g, \'\') != "")
                    {
                        var newquery = {
                            id: value.replace(/,\s?/g, ","),
                            text: value.replace(/,\s?/g, ",")
                        };
                        newqueries.push(newquery);
                    }
                });
                callback(newqueries);
            }
        }
    })
}
// -->
</script>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_editscene_showthread',
        'template' => $db->escape_string('<div class="float_right"><a onclick="$(\'#ipt_edit\').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== \'undefined\' ? modal_zindex : 9999) }); return false;" style="cursor: pointer;">{$lang->ipt_edit}</a>	<div class="modal" id="ipt_edit" style="display: none;"><form method="post" action="showthread.php?tid={$tid}" id="edit_ipt">
<table>
<tr>
	<td class="tcat" colspan="2"><strong>{$lang->ipt_editscene}</strong></td>
</tr>
<tr>
	<tr>
		<td class="trow1"><strong>{$lang->ipt_charas}</strong></td></tr>
<tr>
		<td class="trow1"><input type="text" class="textbox" name="charas" id="charas" size="40" maxlength="1155" value="{$thread[\'charas\']}" style="min-width: 347px; max-width: 100%;" /> </td>
</tr>
<tr>
		<td class="trow2"><strong>{$lang->ipt_date}</strong></td>	</tr>
<tr>
		<td class="trow2"><input type="date" class="textbox" name="date" id="date" size="40" maxlength="1155" value="{$thread[\'date\']}" /> <input type="text" class="textbox" name="time" id="time"  size="40" maxlength="1155" value="{$thread[\'time\']}"   style="width: 200px;"/> </td>
</tr>		
<tr>
		<td class="trow1"><strong>{$lang->ipt_place}</strong></td>	</tr>
<tr>
		<td class="trow1"><input type="text" class="textbox" name="place" id="place" size="40" maxlength="1155" value="{$thread[\'place\']}"   style="width: 200px;"></td>
</tr>
				<tr>
		<td class="trow1"><strong>{$lang->ipt_scenekind}</strong></td></tr>
<tr>
		<td class="trow1"><select name="add_charas">
			{$scenestatus}
			</select></td>
</tr>
	<tr><td class="trow2" align="center"><input type="submit" name="edit_ipt"  id="edit_ipt" value="{$lang->ipt_edit}" class="button"></td></tr>
</table>
	</form></div></div>
<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
<script type="text/javascript">
<!--
if(use_xmlhttprequest == "1")
{
    MyBB.select2();
    $("#charas").select2({
        placeholder: "{$lang->search_user}",
        minimumInputLength: 2,
        maximumSelectionSize: \'\',
        multiple: true,
        ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
            url: "xmlhttp.php?action=get_users",
            dataType: \'json\',
            data: function (term, page) {
                return {
                    query: term, // search term
                };
            },
            results: function (data, page) { // parse the results into the format expected by Select2.
                // since we are using custom formatting functions we do not need to alter remote JSON data
                return {results: data};
            }
        },
        initSelection: function(element, callback) {
            var query = $(element).val();
            if (query !== "") {
                var newqueries = [];
                exp_queries = query.split(",");
                $.each(exp_queries, function(index, value ){
                    if(value.replace(/\s/g, \'\') != "")
                    {
                        var newquery = {
                            id: value.replace(/,\s?/g, ","),
                            text: value.replace(/,\s?/g, ",")
                        };
                        newqueries.push(newquery);
                    }
                });
                callback(newqueries);
            }
        }
    })
}
// -->
</script>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_forumdisplay',
        'template' => $db->escape_string('<div class="ipt_forumdisplay">
  <div class="fd_scenecharas smalltext">{$thread[\'charas\']}</div>
  <div class="fd_scenedatetime smalltext">{$thread[\'date\']}, {$thread[\'time\']}</div>
  <div class="fd_sceneplace smalltext">{$thread[\'place\']}</div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_global',
        'template' => $db->escape_string('<a href="misc.php?action=inplayscenes">{$lang->ipt_global} ({$openscenes}/{$allscenes})</a>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_misc',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->ipt_inplaytracker}</title>
{$headerinclude}
</head>
<body>
{$header}
<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
	<tr><td class="thead"><strong>{$lang->ipt_inplaytracker} ({$lang->ipt_allscenes})</strong></td></tr>
		{$ipt_misc_charas}
	</table>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_misc_charas',
        'template' => $db->escape_string('<tr><td>
<div class="charaterbox">
	<strong>{$chara} ({$charascenes})</strong>
</div>
{$ipt_misc_scenes}
	</td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_misc_scenes',
        'template' => $db->escape_string('<div class="scenes">
  <div class="scenestatus">{$scenestatus}</div>
  <div class="sceneinformation">
	  <strong>{$scenes[\'subject\']}</strong>
	  <div class="scenecharas">{$scenes[\'charas\']}</div>
	  <div class="scenesmallinfos">{$scenes[\'date\']}, {$scenes[\'time\']} | {$scenes[\'place\']}</div>
	</div>
  <div class="scenelastpost">
	  <strong>{$lastscenepost}</strong>
	  <div class="scenesmallinfos">{$lang->by} <strong>{$scenes[\'lastposter\']}</strong></div>
	  <div class="scenesmallinfos">{$scenes[\'lastpost\']}</div>
	</div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_newscene',
        'template' => $db->escape_string('<tr>
	<td class="tcat" colspan="2"><strong>{$lang->ipt_newscene}</strong></td>
</tr>
<tr>
	<tr>
		<td class="trow1"><strong>{$lang->ipt_charas}</strong></td>
		<td class="trow1"><input type="text" class="textbox" name="charas" id="charas" size="40" maxlength="1155" value="{$charas}" style="min-width: 347px; max-width: 100%;" /> </td>
</tr>
<tr>
		<td class="trow2"><strong>{$lang->ipt_date}</strong></td>	
		<td class="trow2"><input type="date" class="textbox" name="date" id="date" size="40" maxlength="1155" value="{$date}" /> <input type="text" class="textbox" name="time" id="time"  size="40" maxlength="1155" value="{$time}" /> </td>
</tr>		
<tr>
		<td class="trow2"><strong>{$lang->ipt_place}</strong></td>	
		<td class="trow2"><input type="text" class="textbox" name="place" id="place" size="40" maxlength="1155" value="{$place}"></td>
</tr>
			<tr>
		<td class="trow1"><strong>{$lang->ipt_scenekind}</strong></td>
		<td class="trow1"><select name="add_charas">
			{$scenestatus}
			</select></td>
</tr>

<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
<script type="text/javascript">
<!--
if(use_xmlhttprequest == "1")
{
    MyBB.select2();
    $("#charas").select2({
        placeholder: "{$lang->search_user}",
        minimumInputLength: 2,
        maximumSelectionSize: \'\',
        multiple: true,
        ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
            url: "xmlhttp.php?action=get_users",
            dataType: \'json\',
            data: function (term, page) {
                return {
                    query: term, // search term
                };
            },
            results: function (data, page) { // parse the results into the format expected by Select2.
                // since we are using custom formatting functions we do not need to alter remote JSON data
                return {results: data};
            }
        },
        initSelection: function(element, callback) {
            var query = $(element).val();
            if (query !== "") {
                var newqueries = [];
                exp_queries = query.split(",");
                $.each(exp_queries, function(index, value ){
                    if(value.replace(/\s/g, \'\') != "")
                    {
                        var newquery = {
                            id: value.replace(/,\s?/g, ","),
                            text: value.replace(/,\s?/g, ",")
                        };
                        newqueries.push(newquery);
                    }
                });
                callback(newqueries);
            }
        }
    })
}
// -->
</script>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_profile',
        'template' => $db->escape_string('<br/>
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed">
	<tr>
		<td>
		<div class="profilescenes">
			<div class="activescenestitle thead"><strong>{$lang->ipt_activescenes} ({$activescenes})</strong></div>
			<div class="closedscenestitle thead"><strong>{$lang->ipt_closedscenes} ({$closedscenes})</strong></div>
  <div class="activescenes trow1">{$ipt_activescenes}</div>
  <div class="closedscenes trow2">{$ipt_closedscenes}</div>
</div>
		</td>
	</tr>
</table>
<br/>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_profile_bit',
        'template' => $db->escape_string('<div class="scene">
	<strong>{$scenetitle}</strong> ({$ownposts} von {$writeposts} {$lang->ipt_writeposts})
	<div class="scenecaras">{$scenecharas}</div>
	<div class="sceneinfos">{$scenedate}, {$scenetime} | {$sceneplace}</div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_reminder',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->ipt_reminder}</title>
{$headerinclude}
</head>
<body>
{$header}
<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
	<tr><td class="thead"><strong>{$lang->ipt_sincedays}</strong></td></tr>
		{$ipt_reminder_charas}
	</table>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_reminder_alert',
        'template' => $db->escape_string('<div class="red_alert">
	<strong>
		<a href="misc.php?action=postreminder">{$lang->ipt_reminder}</a></strong> 
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_reminder_charas',
        'template' => $db->escape_string('<tr><td>
<div class="charaterbox">
	<strong>{$chara}</strong>
</div>
{$ipt_reminder_scenes}
	</td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_reminder_scenes',
        'template' => $db->escape_string('<div class="reminder_scenes">
  <div class="reminder_sceneinformation">
	  <strong>{$scenes[\'subject\']}</strong>
	  <div class="scenecharas">{$scenes[\'charas\']}</div>
	  <div class="scenesmallinfos">{$scenes[\'date\']}, {$scenes[\'time\']} | {$scenes[\'place\']}</div>
	</div>
  <div class="reminder_scenelastpost">
	  <strong>{$lastscenepost}</strong>
	  <div class="scenesmallinfos">{$lang->by} <strong>{$scenes[\'lastposter\']}</strong></div>
	  <div class="scenesmallinfos">{$scenes[\'lastpost\']}</div>
	</div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_showthread',
        'template' => $db->escape_string('<tr>
	<td class="trow1">{$ipt_edit}
		<div class="ipt_showthread">
  <div class="ipt_scenecharas">
	  <div class="scenefacts">{$charas} {$ipt_addchara}</div>
	  <div class="scenepiont">{$lang->ipt_stcharas}</div>
			</div>
  <div class="ipt_scenedate">	  <div class="scenefacts">{$date}</div>
	  <div class="scenepiont">{$lang->ipt_stdate}</div></div>
  <div class="ipt_scenetime">	  <div class="scenefacts">{$thread[\'time\']}</div>
	  <div class="scenepiont">{$lang->ipt_sttime}</div></div>
  <div class="ipt_sceneplace">	  <div class="scenefacts">{$thread[\'place\']}</div>
	  <div class="scenepiont">{$lang->ipt_stplace}</div></div>
</div>
	</td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'ipt_showthread_addcharas',
        'template' => $db->escape_string('<a href="misc.php?action=add_charas&scene_charas={$thread[\'charas\']}&scene_tid={$tid}" title="{$lang->ipt_addchara}">{$lang->ipt_addchara_plus}</a>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //CSS einfügen
    $css = array(
        'name' => 'inplaytracker.css',
        'tid' => 1,
        'attachedto' => '',
        "stylesheet" => '/*forumdisplay*/

.ipt_forumdisplay{
  display: grid; 
  grid-template-columns: 30% 70%; 
  grid-template-rows: min-content min-content; 
  gap: 0px 0px; 
  grid-template-areas: 
    "fd_scenecharas fd_scenecharas"
    "fd_scenedatetime fd_sceneplace"; 
	margin: 2px;
}
.fd_scenecharas { grid-area: fd_scenecharas; }
.fd_scenedatetime { grid-area: fd_scenedatetime; }
.fd_sceneplace { grid-area: fd_sceneplace; }

/*Showthread*/
.ipt_showthread {  display: grid;
   grid-template-columns: 40% 15% 15% 30%; 
  grid-template-rows: 1fr;
  gap: 5px 2px;
	margin: 0 5px;
  grid-auto-flow: row;
  grid-template-areas:
    "ipt_scenecharas ipt_scenedate ipt_scenetime ipt_sceneplace";
	text-align: center;
}

.ipt_scenecharas { grid-area: ipt_scenecharas; 
	box-sizing: border-box;
	padding: 5px 10px;}

.ipt_scenedate { grid-area: ipt_scenedate;
	box-sizing: border-box;
	padding: 5px 10px; }

.ipt_scenetime { grid-area: ipt_scenetime; 
	box-sizing: border-box;
	padding: 5px 10px;}

.ipt_sceneplace { grid-area: ipt_sceneplace; 
	box-sizing: border-box;
	padding: 5px 10px;}

.scenefacts{
	padding: 5px 0 5px 20px;
}


.scenepiont{
	font-size: 10px;
	font-weight: bold;
	text-transform: uppercase;
}

/*Misc*/

.charaterbox{
	padding: 10px 20px;
	background: #efefef;
}

.scenes {  display: grid;
  grid-template-columns: 20% 60% 20%;
  grid-template-rows: 1fr;
  gap: 5px 5px;
  grid-auto-flow: row;
  grid-template-areas:
    "scenestatus sceneinformation scenelastpost";
	align-items: center;
	margin: 5px 0;
}

.scenestatus { grid-area: scenestatus;
text-align: center;}

.openscene{
	font-weight: bold;
	color: red;
}

.waitscene{
	color: green;	
}

.sceneinformation { grid-area: sceneinformation; }

.scenecharas{
	font-size: 12px;	
}

.scenesmallinfos{
	font-size: 10px;	
}

.scenelastpost { grid-area: scenelastpost; }


/*Profile*/
.profilescenes {  display: grid;
  grid-template-columns: 50% 50%;
  grid-template-rows: max-content 1fr;
  grid-auto-flow: row;
gap: 2px 1px;
  grid-template-areas:
    "activescenestitle closedscenestitle"
    "activescenes closedscenes";
}

.activescenestitle { grid-area: activescenestitle; }

.closedscenestitle { grid-area: closedscenestitle; }

.activescenes { grid-area: activescenes; }

.closedscenes { grid-area: closedscenes; }

.scene{
	padding: 2px 5px;
	margin: 3px auto;
}

.scenecaras{
	font-size: 11px;	
}

.sceneinfos{
	font-size: 9px;	
}

/*Scenereminder*/
.reminder_scenes {  display: grid;
  grid-template-columns: 70% 30%;
  grid-template-rows: 1fr;
  gap: 5px 5px;
  grid-auto-flow: row;
  grid-template-areas:
    "sceneinformation scenelastpost";
	align-items: center;
	margin: 5px 0;
}

.reminder_sceneinformation { grid-area: sceneinformation; }


.reminder_scenelastpost { grid-area: scenelastpost; }
        ',
        'cachefile' => $db->escape_string(str_replace('/', '', 'inplaytracker.css')),
        'lastmodified' => time()
    );

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);

    $tids = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }
    rebuild_settings();
}

function inplaytracker_is_installed()
{
    global $db;
    if ($db->field_exists("charas", "threads")) {
        return true;
    }
    return false;
}

function inplaytracker_uninstall()
{
    global $db;

    //threadstabelle
    if ($db->field_exists("charas", "threads")) {
        $db->drop_column("threads", "charas");
    }

    if ($db->field_exists("date", "threads")) {
        $db->drop_column("threads", "date");
    }

    if ($db->field_exists("place", "threads")) {
        $db->drop_column("threads", "place");
    }

    if ($db->field_exists("time", "threads")) {
        $db->drop_column("threads", "time");
    }
    if ($db->field_exists("add_charas", "threads")) {
        $db->drop_column("threads", "add_charas");
    }
    $db->query("DELETE FROM " . TABLE_PREFIX . "settinggroups WHERE name='inplaytracker'");
    $db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='ipt_inplay_id'");
    $db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='ipt_archive_id'");
    $db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='ipt_scenereminder'");
    $db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='ipt_messager'");
    $db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='ipt_messager_id'");

    $db->delete_query("templates", "title LIKE '%ipt%'");

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    $db->delete_query("themestylesheets", "name = 'inplaytracker.css'");
    $query = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list($theme['tid']);
    }

    rebuild_settings();
}

function inplaytracker_activate()
{
    global $db, $cache;

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if (!$alertTypeManager) {
            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('alert_ipt_newscene'); // The codename for your alert type. Can be any unique string.
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('alert_ipt_newreply'); // The codename for your alert type. Can be any unique string.
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);
    }

    require MYBB_ROOT . "/inc/adminfunctions_templates.php";

    find_replace_templatesets("header", "#" . preg_quote('{$pm_notice}') . "#i", '{$ipt_reminder} {$pm_notice}');
    find_replace_templatesets("header_welcomeblock_member", "#" . preg_quote('{$admincplink}') . "#i", '{$admincplink} {$ipt_global}');
    find_replace_templatesets("member_profile", "#" . preg_quote('{$bannedbit}') . "#i", '{$bannedbit} {$ipt_profile}');
    find_replace_templatesets("newthread", "#" . preg_quote('{$posticons}') . "#i", '{$posticons} {$ipt_newscene}');
    find_replace_templatesets("editpost", "#" . preg_quote('{$posticons}') . "#i", '{$posticons} {$ipt_editscene}');
    find_replace_templatesets("showthread", "#" . preg_quote('<tr><td id="posts_container">') . "#i", ' {$ipt_showthread}<tr><td id="posts_container">');
}

function inplaytracker_deactivate()
{
    global $db, $cache;

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if (!$alertTypeManager) {
            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }

        $alertTypeManager->deleteByCode('alert_ipt_newscene');
        $alertTypeManager->deleteByCode('alert_ipt_newreply');
    }


    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote('{$ipt_reminder}') . "#i", '', 0);
    find_replace_templatesets("header_welcomeblock_member", "#" . preg_quote('{$ipt_global}') . "#i", '', 0);
    find_replace_templatesets("member_profile", "#" . preg_quote('{$ipt_profile}') . "#i", '', 0);
    find_replace_templatesets("newthread", "#" . preg_quote('{$ipt_newscene}') . "#i", '', 0);
    find_replace_templatesets("editpost", "#" . preg_quote('{$ipt_editscene}') . "#i", '', 0);
    find_replace_templatesets("showthread", "#" . preg_quote('{$ipt_showthread}') . "#i", '', 0);

}


// ADMIN-CP PEEKER
$plugins->add_hook('admin_config_settings_change', 'inplaytracker_settings_change');
$plugins->add_hook('admin_settings_print_peekers', 'inplaytracker_settings_peek');
function inplaytracker_settings_change()
{
    global $db, $mybb, $inplaytracker_settings_peeker;

    $result = $db->simple_select('settinggroups', 'gid', "name='inplaytracker'", array("limit" => 1));
    $group = $db->fetch_array($result);
    $inplaytracker_settings_peeker = ($mybb->input['gid'] == $group['gid']) && ($mybb->request_method != 'post');
}
function inplaytracker_settings_peek(&$peekers)
{
    global $mybb, $inplaytracker_settings_peeker;

    if ($inplaytracker_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_ipt_messager"), $("#row_setting_ipt_messager_id"),/1/,true)';
    }
}


/* Newthread Informationen 
 * Hier geht es erstmal darum die Daten in New Thread zu übergeben. Hier wird das Template dafür ausgelesen und es sorgt dafür, dass die Daten nicht verschwinden, wenn man mal auf Vorschau geht.
 */

function inplaytracker_newscene()
{
    global $mybb, $forum, $templates, $lang, $charas, $date, $time, $place, $ipt_newscene, $post_errors, $scenestatus;
    //Die Sprachdatei
    $lang->load('inplaytracker');

    // variabeln leeren
    $inplay_cat = "";
    $messager = "";
    $messager_id = 0;
    // variabel füllen
    $inplay_cat = $mybb->settings['ipt_inplay_id'];
    $messager = $mybb->settings['ipt_messager'];
    $messager_id = $mybb->settings['ipt_messager_id'];

    $add_charas = array(
        "0" => "{$lang->ipt_addcharas_no}",
        "1" => "{$lang->ipt_addcharas_yes}"
    );

    foreach ($add_charas as $key => $value) {
        $scenestatus .= "<option value='{$key}'>{$value}</option'>";
    }

    $date = "01.01.2024";


    // den Szeneneröffner schon mal in den array packen
    $thread['charas'] = $mybb->user['username'] . $mybb->get_input('charas');

    $forum['parentlist'] = "," . $forum['parentlist'] . ",";
    if (preg_match("/,$inplay_cat,/i", $forum['parentlist'])) {
        if ($mybb->input['previewpost'] || $post_errors) {
            $charas = htmlspecialchars($mybb->input['charas']);
            $date = $mybb->input['date'];
            $time = htmlspecialchars($mybb->get_input('time'));
            $place = htmlspecialchars($mybb->get_input('place'));
            foreach ($add_charas as $key => $value) {
                $checked = "";
                if ($key == $mybb->input['add_charas']) {
                    $checked = "selected";
                    $scenestatus .= "<option value='{$key}' {$checked}>{$value}</option'>";
                }
            }
        } else {
            $charas = htmlspecialchars($thread['charas']);
            $date = $thread['date'];
            $time = htmlspecialchars($thread['time']);
            $place = htmlspecialchars($thread['place']);
            foreach ($add_charas as $key => $value) {
                $checked = "";
                if ($key == $thread['add_charas']) {
                    $checked = "selected";
                    $scenestatus .= "<option value='{$key}' {$checked}>{$value}</option'>";
                }
            }
        }

        if ($messager == 1) {
            if ($forum['fid'] == $messager_id) {
                $ipt_newscene = "";
            } else {
                eval ("\$ipt_newscene = \"" . $templates->get("ipt_newscene") . "\";");
            }
        } else {
            eval ("\$ipt_newscene = \"" . $templates->get("ipt_newscene") . "\";");
        }

    }
}


/*
 * Dann wollen wir doch alles mal in die Datenbank speichern und unsere Szenenpartner darüber informieren, dass wir eine neue Szene mit ihnen eröffnet haben.
 * und es werden auch noch Alerts ausgelöst. Wie schön.
 */

function inplaytracker_newscene_do()
{
    global $db, $mybb, $templates, $tid, $forum;
    // variabeln leeren
    $inplay_cat = "";
    $all_charas = "";
    $charas = "";
    $date = "";
    $time = "";
    $place = "";
    $scenestatus = "";

    // variabel füllen
    $inplay_cat = $mybb->settings['ipt_inplay_id'];

    $forum['parentlist'] = "," . $forum['parentlist'] . ",";
    if (preg_match("/,$inplay_cat,/i", $forum['parentlist'])) {
        $all_charas = explode(',', $mybb->input['charas']);
        $all_charas = array_map("trim", $all_charas);
        //wir legen uns einen Array an, den wir mit den Charakternamen füllen
        $charas = array();

        foreach ($all_charas as $chara) {
            $chara = $db->escape_string($chara);

            // einmal durch die Datenbank jagen
            $query = $db->simple_select("users", "*", "username='" . $chara . "'");
            $row = $db->fetch_array($query);

            $charaname = $row['username'];
            $uid = $row['uid'];
            $sceneopener = $mybb->user['uid'];

            // Alert auslösen, weil wir wollen ja bescheid wissen, ne?!
            if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('alert_ipt_newscene');
                if ($alertType != NULL && $alertType->getEnabled() && $sceneopener != $uid) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int) $uid, $alertType, (int) $tid);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                }
            }

            // und ab in den Array
            $charas[] = $charaname;
        }

        // und ab in die Datenbank damit :D wir wollen es ja noch auslesen könne

        $charas = implode(",", $charas);
        $charas = $db->escape_string($charas);
        $time = $db->escape_string($_POST['time']);
        $date = $_POST['date'];
        $place = $db->escape_string($_POST['place']);
        $scenestatus = $_POST['add_charas'];

        $newscene = array(
            "charas" => $charas,
            "date" => $date,
            "time" => $time,
            "place" => $place,
            "add_charas" => $scenestatus
        );

        $db->update_query("threads", $newscene, "tid = {$tid}");

    }
}

/* Wir möchten die Szene auch editieren */
function inplaytracker_editscene()
{
    global $mybb, $db, $templates, $forum, $thread, $post_errors, $lang, $ipt_editscene, $scenestatus;

    // variabel leeren
    $inplay_cat = "";
    $messager = "";
    $messager_id = 0;
    $archive_forum = "";

    // variabel füllen
    $inplay_cat = $mybb->setting['ipt_inplay_id'];
    $archive_forum = $mybb->setting['ipt_archive_id'];
    $messager = $mybb->settings['ipt_messager'];
    $messager_id = $mybb->settings['ipt_messager_id'];
    // und einmal auslesen

    $forum['parentlist'] = "," . $forum['parentlist'] . ",";
    if (preg_match("/,$inplay_cat,/i", $forum['parentlist']) or preg_match("/$archive_forum,/i", $forum['parentlist'])) {
        $pid = $mybb->get_input('pid', MyBB::INPUT_INT);
        $add_charas = array(
            "0" => "{$lang->ipt_addcharas_no}",
            "1" => "{$lang->ipt_addcharas_yes}"
        );

        if ($thread['firstpost'] == $pid) {
            if ($mybb->input['previewpost'] || $post_errors) {
                $charas = htmlspecialchars($mybb->input['charas']);
                $date = $mybb->input['date'];
                $time = htmlspecialchars($mybb->get_input('time'));
                $place = htmlspecialchars($mybb->get_input('place'));
                foreach ($add_charas as $key => $value) {
                    $checked = "";
                    if ($key == $mybb->input['add_charas']) {
                        $checked = "selected";
                    }
                    $scenestatus .= "<option value='{$key}' {$checked}>{$value}</option'>";

                }
            } else {
                $charas = htmlspecialchars($thread['charas']);
                $date = $thread['date'];
                $time = htmlspecialchars($thread['time']);
                $place = htmlspecialchars($thread['place']);
                foreach ($add_charas as $key => $value) {
                    $checked = "";
                    if ($key == $thread['add_charas']) {
                        $checked = "selected";
                    }
                    $scenestatus .= "<option value='{$key}' {$checked}>{$value}</option'>";

                }
            }

            if ($messager == 1) {
                if ($forum['fid'] == $messager_id) {
                    $ipt_newscene = "";
                } else {
                    eval ("\$ipt_editscene = \"" . $templates->get("ipt_editscene") . "\";");
                }
            } else {
                eval ("\$ipt_editscene = \"" . $templates->get("ipt_editscene") . "\";");
            }

        }

    }
}

/* geänderte Daten  in die Datenbank überspielen*/
function inplaytracker_editscene_do()
{
    global $mybb, $forum, $db, $templates, $thread, $tid;
    // variabel leeren
    $inplay_cat = "";
    $archive_forum = "";

    // variabel füllen
    $inplay_cat = $mybb->setting['ipt_inplay_id'];
    $archive_forum = $mybb->setting['ipt_archive_id'];

    $forum['parentlist'] = "," . $forum['parentlist'] . ",";
    if (preg_match("/,$inplay_cat,/i", $forum['parentlist']) or preg_match("/$archive_forum,/i", $forum['parentlist'])) {

        $charas = $db->escape_string($mybb->input['charas']);
        $date = $mybb->input['date'];
        $time = $db->escape_string($mybb->input['time']);
        $place = $db->escape_string($mybb->input['place']);
        $scenestatus = $mybb->input['add_charas'];

        $editscene = array(
            "charas" => $charas,
            "date" => $date,
            "time" => $time,
            "place" => $place,
            "add_charas" => $scenestatus
        );

        $db->update_query("threads", $editscene, "tid = {$tid}");

    }

}

/* 
 * Natürlich soll eine Alert auch ausgelöst werden, wenn wir auf eine Szene antworten. Muss ja alles seine Richtigkeit haben. Hier werden aber keine Informationen übergeben, da ja schon alles
 * im ersten Post steht. :) Hier geht es somit fast nur um die Alerts
 */

function inplaytracker_reply_do()
{
    global $db, $mybb, $templates, $lang, $forum, $thread, $tid;
    $lang->load('inplaytracker');
    // variabeln leeren
    $inplay_cat = "";

    // variabel füllen
    $inplay_cat = $mybb->settings['ipt_inplay_id'];

    $forum['parentlist'] = "," . $forum['parentlist'] . ",";
    if (preg_match("/,$inplay_cat,/i", $forum['parentlist'])) {

        // einmal alle Charaktere schnappen und auseinander nehmen
        $all_charas = explode(', ', $thread['charas']);
        $subject = $thread['subject'];
        $last_post = $db->fetch_field($db->query("SELECT pid FROM " . TABLE_PREFIX . "posts WHERE tid = '$tid' ORDER BY pid DESC LIMIT 1"), "pid");
        foreach ($all_charas as $chara) {
            $chara = htmlspecialchars($chara);

            $query = $db->simple_select("users", "uid", "username='" . $chara . "'");

            $uid = $db->fetch_field($query, "uid");
            $answer_uid = $mybb->user['uid'];

            // Alert auslösen, weil wir wollen ja bescheid wissen, ne?!
            if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('alert_ipt_newreply');
                if ($alertType != NULL && $alertType->getEnabled() && $answer_uid != $uid) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int) $uid, $alertType, (int) $tid);
                    $alert->setExtraDetails([
                        'subject' => $subject,
                        'lastpost' => $last_post
                    ]);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                }
            }

        }

    }
}


/*
 * Thread auslesen im Showthread, weil wir möchten das ja auch alles im Thread oben stehen haben, weil fancy!
 */

function inplaytracker_showthread()
{
    global $thread, $db, $mybb, $forum, $templates, $lang, $ipt_showthread, $charas, $charaname, $chara, $ipt_edit, $date, $charas, $tid, $ipt_addchara, $scenestatus;
    $lang->load('inplaytracker');


    // variabel leeren
    $inplay_cat = "";
    $archive_forum = "";
    $charas = "";
    $chara = '';
    $ipt_edit = "";
    // variabel füllen
    $inplay_cat = $mybb->settings['ipt_inplay_id'];
    $archive_forum = $mybb->settings['ipt_archive_id'];

    // array anlegen
    $charalist = array();
    // und einmal auslesen
    $forum['parentlist'] = "," . $forum['parentlist'] . ",";
    if (preg_match("/,$inplay_cat,/i", $forum['parentlist']) or preg_match("/$archive_forum,/i", $forum['parentlist'])) {
        // Erstmal den Eintrag zu einen Array machen, so dass wir mit der foreach durchgehen können
        $charas = explode(",", $thread['charas']);
        $add_charas = array(
            "0" => "{$lang->ipt_addcharas_no}",
            "1" => "{$lang->ipt_addcharas_yes}"
        );

        foreach ($add_charas as $key => $value) {
            $checked = "";
            if ($key == $thread['add_charas']) {
                $checked = "selected";
            }
            $scenestatus .= "<option value='{$key}' {$checked}>{$value}</option'>";

        }

        // Account sollen bitte in ihren Gruppenfarben dargestellt werden und dann wieder im Array gespeichert werden. 
        // Accounts die nicht mehr existieren sollen nur als Text dargestellt werden.
        // wir gehen somit erstmal alle Array-Einträge, welche wir durch die explode generiert haben, mit der foreach durch und übergeben sie $chara. Diese können wir dann Einzeln betrachten.
        foreach ($charas as $chara) {

            $ipt_edit = "";
            $ipt_addchara = "";

            $chara = $db->escape_string($chara);
            $chara_query = $db->simple_select("users", "*", "username ='$chara'");
            $charaktername = $db->fetch_array($chara_query);
          

            if ($charaktername['uid'] == $mybb->user['uid']) {
                eval ("\$ipt_edit = \"" . $templates->get("ipt_editscene_showthread") . "\";");
            }


            if ($charaktername['uid'] != $mybb->user['uid'] && $thread['uid'] != $mybb->user['uid'] && $thread['add_charas'] == 1) {
                eval ("\$ipt_addchara = \"" . $templates->get("ipt_showthread_addcharas") . "\";");
            } else{
                $ipt_addchara = "";
            }

            if (!empty($charaktername)) {
                $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                $chara = build_profile_link($username, $charaktername['uid']);

            } else {
                $chara = $chara;
            }



            array_push($charalist, $chara);
        }

        //lasst uns die Charas wieder zusammenkleben :D
        $charas = implode(" • ", $charalist);

        // Datum formatieren
        $date = strtotime($thread['date']);
        $date = date("d.m.Y", $date);

        eval ("\$ipt_showthread = \"" . $templates->get("ipt_showthread") . "\";");
    }


    if (isset($mybb->input['edit_ipt'])) {
        $charas = $db->escape_string($mybb->input['charas']);
        $date = $mybb->input['date'];
        $time = $db->escape_string($mybb->input['time']);
        $place = $db->escape_string($mybb->input['place']);
        $add_charas = $mybb->input['add_charas'];

        $editscene = array(
            "charas" => $charas,
            "date" => $date,
            "time" => $time,
            "place" => $place,
            "add_charas" => $add_charas
        );

        $db->update_query("threads", $editscene, "tid = {$tid}");
        redirect("showthread.php?tid={$tid}");
    }
}

// bei den Threads wird es auch noch angezeigt
function inplaytracker_forumdisplay(&$thread)
{
    global $db, $mybb, $templates, $thread, $foruminfo, $lang, $ipt_forumdisplay;
    $lang->load("inplaytracker");
    // variabel leeren
    $inplay_cat = "";
    $archive_forum = "";
    $charas = "";
    $chara = '';
    $ipt_forumdisplay = "";

    // variabel füllen
    $inplay_cat = $mybb->settings['ipt_inplay_id'];
    $archive_forum = $mybb->settings['ipt_archive_id'];
    // array anlegen
    $charalist = array();
    // und einmal auslesen
    $foruminfo['parentlist'] = "," . $foruminfo['parentlist'] . ",";
    if (preg_match("/,$inplay_cat,/i", $foruminfo['parentlist']) or preg_match("/,$archive_forum,/i", $foruminfo['parentlist'])) {
        // Erstmal den Eintrag zu einen Array machen, so dass wir mit der foreach durchgehen können
        $charas = explode(",", $thread['charas']);

        // Account sollen bitte in ihren Gruppenfarben dargestellt werden und dann wieder im Array gespeichert werden. 
        // Accounts die nicht mehr existieren sollen nur als Text dargestellt werden.
        // wir gehen somit erstmal alle Array-Einträge, welche wir durch die explode generiert haben, mit der foreach durch und übergeben sie $chara. Diese können wir dann Einzeln betrachten.
        foreach ($charas as $chara) {
            $chara = $db->escape_string($chara);
            $chara_query = $db->simple_select("users", "*", "username ='$chara'");
            $charaktername = $db->fetch_array($chara_query);
            if (!empty($charaktername)) {
                $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                $charalink = build_profile_link($username, $charaktername['uid']);
            } else {
                $charalink = $chara;
            }
            array_push($charalist, $charalink);
        }

        //lasst uns die Charas wieder zusammenkleben :D
        $thread['charas'] = implode(" • ", $charalist);

        // Datum formatieren
        $thread['date'] = strtotime($thread['date']);
        $thread['date'] = date("d.m.Y", $thread['date']);
        eval ("\$ipt_forumdisplay = \"" . $templates->get("ipt_forumdisplay") . "\";");
    }
}


// Alle Szenen in der Übersicht und für alle Charaktere
function inplaytracker_misc()
{
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $db, $ipt_misc_charas, $chara, $charascenes, $aktivescene, $openscenes, $opencharascenes, $allscenes, $openscenes, $lastpost, $lastscenepost, $countforgetscenes;
    $lang->load("inplaytracker");

    // variabel leeren und definieren
    $inplay_cat = "";
    $postreminder = "";
    $openscenes = 0;
    $allscenes = 0;

    //welcher user ist online
    $this_user = intval($mybb->user['uid']);

    //für den fall nicht mit hauptaccount online
    $as_uid = intval($mybb->user['as_uid']);

    // variabel füllen
    $inplay_cat = $mybb->settings['ipt_inplay_id'];
    $postreminder = $mybb->settings['ipt_scenereminder'];

    if ($mybb->get_input('action') == 'inplayscenes') {
        // Do something, for example I'll create a page using the hello_world_template

        // Add a breadcrumb
        add_breadcrumb($lang->ipt_inplaytracker, "misc.php?action=inplayscenes");
        // suche alle angehangenen accounts
        if ($as_uid == 0) {
            $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $this_user) OR (uid = $this_user) ORDER BY username ASC");
        } else if ($as_uid != 0) {
            //id des users holen wo alle angehangen sind
            $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $as_uid) OR (uid = $this_user) OR (uid = $as_uid) ORDER BY username ASC");
        }
        $allaktivescene = "Szenen";
        $allopenscene = "Szenen";

        while ($charaselect = $db->fetch_array($select)) {
            $character = $db->escape_string($charaselect["username"]);
            $chara = $charaselect['username'];
            $ipt_misc_scenes = "";
            $charascenes = 0;
            $opencharascenes = 0;
            $aktivescene = "";
            $openscene = "";

            $aktivescene = "Szenen";
            $openscene = "Szenen";


            $scenequery = $db->query("SELECT t.lastposter, t.lastpost, t.date, t.time, t.charas, t.subject, t.place, t.lastposteruid, p.tid
    FROM " . TABLE_PREFIX . "posts p
    LEFT JOIN " . TABLE_PREFIX . "threads t
    on (t.lastpost = p.dateline )
    LEFT JOIN " . TABLE_PREFIX . "forums f
    on (t.fid = f.fid)
    where f.parentlist like '" . $inplay_cat . ",%'
    and t.visible = 1
    and t.charas like '%" . $character . "%'
    ");

            while ($scenes = $db->fetch_array($scenequery)) {
                //alle Szenen hochzählen insgesamt
                $allscenes++;

                if ($allscenes == 1) {
                    $allaktivescene = "Szene";
                } else {
                    $allaktivescene = "Szenen";
                }


                // alle Szenen des Charakters zählen
                $charascenes++;
                if ($charascenes == 1) {
                    $aktivescene = "Szene";
                } else {
                    $aktivescene = "Szenen";
                }

                // variabel jedes Mal leeren, das wir sie neu füllen können und nichts falsches übertragen
                $scenestatus = "";

                // Charaktere auseinander nehmen
                $get_charas = explode(",", $scenes['charas']);

                // jetzt müssen wir den Status abfragen
                $get_lastposter = array_search($scenes['lastposter'], $get_charas);
                $get_lastposter = $get_lastposter + 1;
                $next_chara = $get_charas[$get_lastposter];

                if (!$get_charas[$get_lastposter]) {
                    $next_chara = $get_charas[0];
                }

                if ($next_chara == $chara) {
                    // du bist dran bei deiner Szene
                    $scenestatus = "<div class='openscene'>{$lang->ipt_scenestatus}</div>";

                    // zähle alle offenen Szenen
                    $opencharascenes++;
                    $openscenes++;

                    if ($opencharascenes == 1) {
                        $openscene = "Szene";
                    } else {
                        $openscene = "Szenen";
                    }

                    if ($openscenes == 1) {
                        $allopenscene = "Szene";
                    } else {
                        $allopenscene = "Szenen";
                    }
                } else {
                    // jemand anderes ist dran, bitte dessen Accountname ausgeben
                    $scenestatus = "<div class='waitscene'>{$next_chara}</div>";
                }

                // Account sollen bitte in ihren Gruppenfarben dargestellt werden und dann wieder im Array gespeichert werden. 
                // Accounts die nicht mehr existieren sollen nur als Text dargestellt werden.
                $charalist = array();
                foreach ($get_charas as $chara) {
                    $chara = $db->escape_string($chara);
                    $chara_query = $db->simple_select("users", "*", "username ='$chara'");
                    $charaktername = $db->fetch_array($chara_query);
                    if (!empty($charaktername)) {
                        $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                        $chara = build_profile_link($username, $charaktername['uid']);
                    } else {
                        $chara = $chara;
                    }
                    array_push($charalist, $chara);
                }

                //lasst uns die Charas wieder zusammenkleben :D
                $scenes['charas'] = implode(" • ", $charalist);

                // restliche Informationen auslesen              
                $scenes['date'] = date("d.m.Y", strtotime($scenes['date']));
                $lastscenepost = "<a href='showthread.php?tid={$scenes['tid']}&action=lastpost'>{$lang->ipt_lastpost}</a>";
                $scenes['lastposter'] = build_profile_link($scenes['lastposter'], $scenes['lastposteruid']);
                $scenes['lastpost'] = my_date("relative", $scenes['lastpost']);

                eval ("\$ipt_misc_scenes .= \"" . $templates->get("ipt_misc_scenes") . "\";");
            }
            $charascenes = $lang->sprintf($lang->ipt_charascenes, $opencharascenes, $openscene, $charascenes, $aktivescene);
            $chara = format_name($charaselect['username'], $charaselect['usergroup'], $charaselect['displaygroup']);
            eval ("\$ipt_misc_charas .= \"" . $templates->get("ipt_misc_charas") . "\";");
        }

        $lang->ipt_allscenes = $lang->sprintf($lang->ipt_allscenes, $openscenes, $allopenscene, $allscenes, $allaktivescene);
        // Using the misc_help template for the page wrapper
        eval ("\$page = \"" . $templates->get("ipt_misc") . "\";");
        output_page($page);
    }

    if ($mybb->get_input('action') == 'postreminder') {
        $countforgetscenes = 0;
        // Add a breadcrumb
        add_breadcrumb($lang->ipt_reminder, "misc.php?action=postreminder");
        // suche alle angehangenen accounts
        if ($as_uid == 0) {
            $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $this_user) OR (uid = $this_user) ORDER BY username ASC");
        } else if ($as_uid != 0) {
            //id des users holen wo alle angehangen sind
            $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $as_uid) OR (uid = $this_user) OR (uid = $as_uid) ORDER BY username ASC");
        }

        while ($row = $db->fetch_array($select)) {
            $character = $db->escape_string($row["username"]);
            $chara = $row['username'];
            $ipt_misc_scenes = "";
            $charascenes = 0;
            $opencharascenes = 0;
            $count = 0;


            $scenequery = $db->query("SELECT t.lastposter, t.lastpost, t.date, t.time, t.charas, t.subject, t.place, t.lastposteruid, p.tid
    FROM " . TABLE_PREFIX . "posts p
    LEFT JOIN " . TABLE_PREFIX . "threads t
    on (t.lastpost = p.dateline )
    LEFT JOIN " . TABLE_PREFIX . "forums f
    on (t.fid = f.fid)
    where f.parentlist like '" . $inplay_cat . ",%'
    and t.visible = 1
    and t.charas like '%" . $character . "%'
    and t.lastposter != '" . $character . "' 
    and t.lastpost < (CURDATE() - INTERVAL " . $postreminder . " DAY);
    ");

            while ($scenes = $db->fetch_array($scenequery)) {
                $countforgetscenes++;
                $count = 1;
                // Charaktere auseinander nehmen
                $get_charas = explode(",", $scenes['charas']);

                // Account sollen bitte in ihren Gruppenfarben dargestellt werden und dann wieder im Array gespeichert werden. 
                // Accounts die nicht mehr existieren sollen nur als Text dargestellt werden.
                $charalist = array();
                foreach ($get_charas as $chara) {
                    $chara = $db->escape_string($chara);
                    $chara_query = $db->simple_select("users", "*", "username ='$chara'");
                    $charaktername = $db->fetch_array($chara_query);
                    if (!empty($charaktername)) {
                        $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                        $chara = build_profile_link($username, $charaktername['uid']);
                    } else {
                        $chara = $chara;
                    }
                    array_push($charalist, $chara);
                }

                //lasst uns die Charas wieder zusammenkleben :D
                $scenes['charas'] = implode(" • ", $charalist);

                // restliche Informationen auslesen              
                $scenes['date'] = date("d.m.Y", strtotime($scenes['date']));
                $lastscenepost = "<a href='showthread.php?tid={$scenes['tid']}&action=lastpost'>{$lang->ipt_lastpost}</a>";
                $scenes['lastposter'] = build_profile_link($scenes['lastposter'], $scenes['lastposteruid']);
                $scenes['lastpost'] = my_date("relative", $scenes['lastpost']);

                eval ("\$ipt_reminder_scenes .= \"" . $templates->get("ipt_reminder_scenes") . "\";");
            }
            if ($count == 1) {
                $chara = format_name($row['username'], $row['usergroup'], $row['displaygroup']);
                eval ("\$ipt_reminder_charas .= \"" . $templates->get("ipt_reminder_charas") . "\";");
            }
        }

        if ($countforgetscenes == 1) {
            $lang->ipt_sincedays = $lang->sprintf($lang->ipt_sincedays_1, $postreminder);
        } else {
            $lang->ipt_sincedays = $lang->sprintf($lang->ipt_sincedays, $postreminder);
        }


        // Using the misc_help template for the page wrapper
        eval ("\$page = \"" . $templates->get("ipt_reminder") . "\";");
        output_page($page);
    }

    // Charaktere noch so hinzufügen
    if ($mybb->get_input('action') == 'add_charas') {
        $get_tid = $mybb->input['scene_tid'];
        $new_chara = $mybb->user['uid'];
        $subject = $db->fetch_field($db->query("SELECT subject from " . TABLE_PREFIX . "threads where tid = '$get_tid'"), "subject");
        $last_post = $db->fetch_field($db->query("SELECT pid FROM " . TABLE_PREFIX . "posts WHERE tid = '$get_tid' ORDER BY pid DESC LIMIT 1"), "pid");
        $scenecharas = $mybb->input['scene_charas'];
        $all_charas = explode(",", $scenecharas);
        foreach ($all_charas as $chara) {
            $chara = htmlspecialchars($chara);
            $query = $db->simple_select("users", "uid", "username='" . $chara . "'");

            $uid = $db->fetch_field($query, "uid");

        }

        $get_charas = $scenecharas . "," . $mybb->user['username'];


        $update_scene = array(
            "charas" => $db->escape_string($get_charas),
        );

        $db->update_query("threads", $update_scene, "tid = {$get_tid}");
        redirect("showthread.php?tid={$get_tid}");

    }
}

// global anzeigen

function inplaytracker_global()
{
    global $db, $templates, $mybb, $lang, $allscenes, $openscenes, $ipt_global, $ipt_reminder;

    $lang->load('inplaytracker');

    // variabel leeren und definieren
    $inplay_cat = "";
    $postreminder = "";
    $openscenes = 0;
    $allscenes = 0;
    $watingscenes = 0;

    //welcher user ist online
    $this_user = intval($mybb->user['uid']);

    //für den fall nicht mit hauptaccount online
    $as_uid = intval($mybb->user['as_uid']);

    // variabel füllen
    $inplay_cat = $mybb->settings['ipt_inplay_id'];
    $postreminder = $mybb->settings['ipt_scenereminder'];

    // einmal alle uid ziehen, die entweder Mainaccount oder daran Angehängt sind

    if ($as_uid == 0) {
        $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $this_user) OR (uid = $this_user) ORDER BY username ASC");
    } else if ($as_uid != 0) {
        //id des users holen wo alle angehangen sind
        $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $as_uid) OR (uid = $this_user) OR (uid = $as_uid) ORDER BY username ASC");
    }

    while ($select_charas = $db->fetch_array($select)) {
        $chara = $db->escape_string($select_charas['username']);
        $chara_if = $select_charas['username'];
        // alle Szenen der charaktere zählen und was bei denen offen ist
        $scenequery = $db->query("SELECT t.lastposter, t.lastpost, t.charas, t.lastposteruid
        FROM " . TABLE_PREFIX . "posts p
        LEFT JOIN " . TABLE_PREFIX . "threads t
        on (t.lastpost = p.dateline )
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on (t.fid = f.fid)
        where f.parentlist like '" . $inplay_cat . ",%'
        and t.visible = 1
        and t.charas like '%" . $chara . "%'
        ");

        while ($get_scenes = $db->fetch_array($scenequery)) {
            // alle Szenen einmal hochzählen
            $allscenes++;

            // Charaktere auseinander nehmen
            $get_charas = explode(",", $get_scenes['charas']);

            // jetzt müssen wir abfragen, wer in der Szene dran ist
            $get_lastposter = array_search($get_scenes['lastposter'], $get_charas);
            // geh den array eins weiter, ich brauch ja den nächsten in der Liste
            $get_lastposter = $get_lastposter + 1;
            // übergebe den Charakter auf Position $get_lastposter + 1
            $next_chara = $get_charas[$get_lastposter];
            // wenn nicht existent, dann ist der Charakter auf der ersten Position dran
            if (!$get_charas[$get_lastposter]) {
                $next_chara = $get_charas[0];
            }
            // Wenn Charaktername gleich den Namen des Charas, der als nächstes dran ist, dann offene Szene zählen
            if ($next_chara == $chara) {
                $openscenes++;
            }
        }
        // Posterinnerung

        $query = $db->query("SELECT t.lastpost, t.lastposter
        from " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on (t.fid = f.fid) 
        where f.parentlist like '" . $inplay_cat . ",%'
        and t.visible = 1
        and t.charas like '%" . $chara . "%'
        ");

        while ($row = $db->fetch_array($query)) {
            $lastpost = 0;
            $today = 0;
            $get_days = 0;

            $today = time();
            $lastpost = $row['lastpost'];
            $faktor = 86400;
            $diff_days = $today - $lastpost;
            $get_days = round($diff_days / $faktor);

            if ($postreminder <= $get_days and $chara_if != $row['lastposter']) {
                $watingscenes++;
            }
        }
    }
    eval ("\$ipt_global = \"" . $templates->get("ipt_global") . "\";");

    if ($watingscenes > 0) {
        if ($watingscenes == 1) {
            $lang->ipt_reminder_global = $lang->sprintf($lang->ipt_reminder_global_1, $watingscenes, $postreminder);
        } else {
            $lang->ipt_reminder_global = $lang->sprintf($lang->ipt_reminder_global, $watingscenes, $postreminder);
        }

        eval ("\$ipt_reminder = \"" . $templates->get("ipt_reminder_alert") . "\";");
    }
}

// Szenen im Profile anzeigen
function inplaytracker_member_profile()
{
    global $db, $mybb, $templates, $memprofile, $ipt_profile, $lang, $ipt_activescenes, $ipt_closedscenes, $scenetitle, $scenedate, $sccenecharas, $scenetime, $sceneplace;
    $lang->load['inplaytracker'];

    // variabel leeren
    $inplay_cat = "";
    $archive_forum = "";
    $activescenes = 0;
    $closedscenes = 0;
    $charaprofile = "";

    // variabel füllen
    $inplay_cat = $mybb->settings['ipt_inplay_id'];
    $archive_forum = $mybb->settings['ipt_archive_id'];
    $charaprofile = $db->escape_string($memprofile['username']);
    $charauid = $memprofile['uid'];

    $scenequery = $db->query("SELECT t.charas, t.date, t.time, t.place, t.subject, t.tid, t.replies, p.uid
       FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.tid = p.tid 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
           where f.parentlist like '" . $inplay_cat . ",%'
        AND t.charas like '%" . $charaprofile . "%'
        AND t.visible='1'
        GROUP BY t.tid
        ORDER BY t.date desc, t.subject asc
    ");

    while ($profilescenes = $db->fetch_array($scenequery)) {
        // aktive Szenen hochzählen
        $activescenes++;
        // variabeln leeren
        $scenetitle = "";
        $scenedate = "";
        $scenecharas = "";
        $scenetime = "";
        $sceneplace = "";
        $writeposts = 0;
        $ownposts = 0;
        $tid = 0;

        // variabeln mit Informationen füllen
        $tid = $profilescenes['tid'];
        $scenetitle = "<a href='showthread.php?tid={$tid}'>{$profilescenes['subject']}</a>";

        $get_charas = explode(",", $profilescenes['charas']);
        // Charakter bisschen hübsch machen
        $charalist = array();
        foreach ($get_charas as $chara) {
            $chara = $db->escape_string($chara);
            $chara_query = $db->simple_select("users", "*", "username ='$chara'");
            $charaktername = $db->fetch_array($chara_query);
            if (!empty($charaktername)) {
                $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                $chara = build_profile_link($username, $charaktername['uid']);
            } else {
                $chara = $chara;
            }
            array_push($charalist, $chara);
        }



        //lasst uns die Charas wieder zusammenkleben :D
        $scenecharas = implode(" • ", $charalist);

        // restliche Informationen auslesen              
        $scenedate = date("d.m.Y", strtotime($profilescenes['date']));
        $scenetime = $profilescenes['time'];
        $sceneplace = $profilescenes['place'];
        $writeposts = $profilescenes['replies'] + 1;
        $select_allposts = $db->simple_select("posts", "count(*) as postcount", "uid = '" . $charauid . "' and tid = '" . $tid . "'");
        $ownposts = $db->fetch_field($select_allposts, "postcount");

        eval ("\$ipt_activescenes .= \"" . $templates->get("ipt_profile_bit") . "\";");
    }

    if ($activescenes == 0) {
        $ipt_activescenes = $lang->ipt_noactivescenes;
    }


    // archiv
    $scenequery = $db->query("SELECT t.charas, t.date, t.time, t.place, t.subject, t.tid, t.replies, p.uid
       FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.tid = p.tid 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
        WHERE t.charas like '%" . $charaprofile . "%'
	    AND concat(',',f.parentlist,',') LIKE '%," . $archive_forum . ",%' 
        AND t.visible='1'
		AND t.charas != ''
        GROUP BY t.tid
        ORDER BY t.date desc, t.subject asc
    ");

    while ($profilescenes = $db->fetch_array($scenequery)) {
        // aktive Szenen hochzählen
        $closedscenes++;
        // variabeln leeren
        $scenetitle = "";
        $scenedate = "";
        $scenecharas = "";
        $scenetime = "";
        $sceneplace = "";
        $tid = 0;
        $writeposts = 0;
        $ownposts = 0;

        // variabeln mit Informationen füllen
        $tid = $profilescenes['tid'];
        $scenetitle = "<a href='showthread.php?tid={$tid}'>{$profilescenes['subject']}</a>";

        $get_charas = explode(",", $profilescenes['charas']);
        // Charakter bisschen hübsch machen

        $charalist = array();
        foreach ($get_charas as $chara) {
            $chara = $db->escape_string($chara);
            $chara_query = $db->simple_select("users", "*", "username ='$chara'");
            $charaktername = $db->fetch_array($chara_query);
            if (!empty($charaktername)) {
                $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                $chara = build_profile_link($username, $charaktername['uid']);
            } else {
                $chara = $chara;
            }
            array_push($charalist, $chara);
        }

        //lasst uns die Charas wieder zusammenkleben :D
        $scenecharas = implode(" • ", $charalist);

        // restliche Informationen auslesen              
        $scenedate = date("d.m.Y", strtotime($profilescenes['date']));
        $scenetime = $profilescenes['time'];
        $sceneplace = $profilescenes['place'];
        $writeposts = $profilescenes['replies'] + 1;
        $select_allposts = $db->simple_select("posts", "count(*) as postcount", "uid = '" . $charauid . "' and tid = '" . $tid . "'");
        $ownposts = $db->fetch_field($select_allposts, "postcount");

        eval ("\$ipt_closedscenes .= \"" . $templates->get("ipt_profile_bit") . "\";");
    }

    if ($closedscenes == 0) {
        $ipt_closedscenes = $lang->ipt_noclosedscenes;
    }

    eval ("\$ipt_profile = \"" . $templates->get("ipt_profile") . "\";");
}


// Benachrichtungen generieren
function inplaytracker_alerts()
{
    global $mybb, $lang;
    $lang->load('inplaytracker');

    /**
     * Alert, wenn eine neue Szene eröffnet wurde.
     */
    class MybbStuff_MyAlerts_Formatter_InplaytrackerNewsceneFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->lang->sprintf(
                $this->lang->alert_ipt_newscene,
                $outputAlert['from_user'],
                $outputAlert['dateline']
            );
        }


        /**
         * Init function called before running formatAlert(). Used to load language files and initialize other required
         * resources.
         *
         * @return void
         */
        public function init()
        {
        }

        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/' . get_post_link((int) $alertContent['lastpost'], (int) $alert->getObjectId());
        }
    }


    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
            new MybbStuff_MyAlerts_Formatter_InplaytrackerNewsceneFormatter($mybb, $lang, 'alert_ipt_newscene')
        );
    }

    /**
     * Alert, wenn es eine neue Antwort gibt.
     */
    class MybbStuff_MyAlerts_Formatter_InplaytrackerNewreplyFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->lang->sprintf(
                $this->lang->alert_ipt_newreply,
                $outputAlert['from_user'],
                $alertContent['subject'],
                $outputAlert['dateline']
            );
        }


        /**
         * Init function called before running formatAlert(). Used to load language files and initialize other required
         * resources.
         *
         * @return void
         */
        public function init()
        {
        }

        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/' . get_post_link((int) $alertContent['lastpost'], (int) $alert->getObjectId()) . '#pid' . $alertContent['lastpost'];
        }
    }

    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
            new MybbStuff_MyAlerts_Formatter_InplaytrackerNewreplyFormatter($mybb, $lang, 'alert_ipt_newreply')
        );
    }

}

function inplaytracker_user_activity($user_activity)
{
    global $user;
    if (my_strpos($user['location'], "misc.php?action=inplayscenes") !== false) {
        $user_activity['activity'] = "inplayscenes";
    }

    return $user_activity;
}

function inplaytracker_location_activity($plugin_array)
{
    global $db, $mybb, $lang;
    $lang->load('inplaytracker');
    if ($plugin_array['user_activity']['activity'] == "inplayscenes") {
        $plugin_array['location_name'] = $lang->ipt_wiw;
    }
    return $plugin_array;
}
