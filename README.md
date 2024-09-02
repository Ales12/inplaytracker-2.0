# inplaytracker-2.0
Mit diesen Plugin könnt ihr eure Inplayszenen im Profil anzeigen lassen und euren Mitspieler darüber informieren, wenn es neue Antworten/eine neue Szene gibt. <br />
Ihr habt zudem die Möglichkeit anzugeben, ob es sich um eine Gruppenszene oder Einzelszene handelt, so dass sich andere in eine Szene hinzufügen können. Alle Szenenpartner haben die Möglichkeit die Informationen über die Szene abzuändern.<br />
<br />
## verbundener Plugin
[Messagersystem by Ales](https://github.com/Ales12/messagersystem)

## neue DB-Felder unter threads
charas, date, time, place und add_charas

## neue Templates
- ipt_editscene 	
- ipt_editscene_showthread 	
- ipt_forumdisplay 	
- ipt_global 	
- ipt_misc 	
- ipt_misc_charas 	
- ipt_misc_scenes 	
- ipt_newscene 	
- ipt_profile 	
- ipt_profile_bit 	
- ipt_reminder 	
- ipt_reminder_alert 	
- ipt_reminder_charas 	
- ipt_reminder_scenes 	
- ipt_showthread 	
- ipt_showthread_addcharas

## Style CSS 
```
/*forumdisplay*/

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
       ```

