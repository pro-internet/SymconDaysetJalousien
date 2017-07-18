<?
class DaysetJalousie extends IPSModule {

	private $InstanceParentID;
	private $dummyGUID;
	
	protected function GetModuleIDByName($name)
	{
		$moduleList = IPS_GetModuleList();
		$GUID = ""; //init
		foreach($moduleList as $l)
		{
			if(IPS_GetModule($l)['ModuleName'] == $name)
			{
				$GUID = $l;
				break;
			}
		}
		return $GUID;
	}
	
	protected function DeleteObject($id)
	{
		if(IPS_HasChildren($id))
		{
			$childrenIDs = IPS_GetChildrenIDs($id);
			foreach($childrenIDs as $chid)
			{
				$this->DeleteObject($chid);
			}
			$this->DeleteObject($id);
		}
		else
		{
			$type = IPS_GetObject($id)['ObjectType'];
			switch($type)
			{
				case(0 /*kategorie*/):
					IPS_DeleteCategory($id);
					break;
				case(1 /*Instanz*/):
					IPS_DeleteInstance($id);
					break;
				case(2 /*Variable*/):
					IPS_DeleteVariable($id);
					break;
				case(3 /*Skript*/):
					IPS_DeleteScript($id,false /*move file to "Deleted" folder*/);
					break;
				case(4 /*Ereignis*/):
					IPS_DeleteEvent($id);
					break;
				case(5 /*Media*/):
					IPS_DeleteMedia($id);
					break;
				case(6 /*Link*/):
					IPS_DeleteLink($id);
					break;
			}
		}
	}
	
	protected function CreateSetValueScript($parentID)
	{
		if(@IPS_GetObjectIDByIdent("SetValueScript", $parentID) === false)
		{
			$sid = IPS_CreateScript(0 /* PHP Script */);
		}
		else
		{
			$sid = IPS_GetObjectIDByIdent("SetValueScript", $parentID);
		}
		IPS_SetParent($sid, $parentID);
			IPS_SetName($sid, "SetValue");
			IPS_SetIdent($sid, "SetValueScript");
			IPS_SetHidden($sid, true);
			IPS_SetPosition($sid, 9999);			
			IPS_SetScriptContent($sid, "<?

if (\$IPS_SENDER == \"WebFront\") 
{ 
    SetValue(\$_IPS['VARIABLE'], \$_IPS['VALUE']); 
} 

?>");

		return $sid;
	}
	
	protected function CreateLink($target, $ident, $parentID, $position)
	{
		if(@IPS_GetObjectIDByIdent($ident, $parentID) === false)
		{
			$lid = IPS_CreateLink();
		}
		else
		{
			$lid = IPS_GetObjectIDByIdent($ident, $parentID);
		}
		$o = IPS_GetObject($target);
		IPS_SetIdent($lid, $ident);
		IPS_SetName($lid, $o['ObjectName']);
		IPS_SetParent($lid, $parentID);
		IPS_SetPosition($lid, $position);
		IPS_SetLinkTargetID($lid, $target);
	}
	
	protected function CreateEvent($name, $ident, $parentID, $type, $trigger, $target, $script)
	{
		if(@IPS_GetObjectIDByIdent($ident, $parentID) === false)
		{
			$eid = IPS_CreateEvent($type);
		}
		else
		{
			$eid = IPS_GetObjectIDByIdent($ident, $parentID);
		}
		IPS_SetEventActive($eid, true);
		IPS_SetEventTrigger($eid, $trigger, $target);
		IPS_SetEventScript($eid, $script);
		IPS_SetName($eid, $name);
		IPS_SetIdent($eid, $ident);
		IPS_SetParent($eid, $parentID);
		
		return $eid;
	}
	
	protected function CreateEventForChildren($parentID, $eventsParent)
	{
		$targets = IPS_GetChildrenIDs($parentID);
		$parentName = IPS_GetName($parentID);
		foreach($targets as $target)
		{
			if(IPS_VariableExists($target))
			{
				$o = IPS_GetObject($target);
				$name = $parentName . $o['ObjectName'] . "onchange";
				$ident = $o['ObjectIdent'] . "onchange";
				$this->CreateEvent($name, $ident, $eventsParent, 0, 1, $target, "DSJal_refresh(" . $this->InstanceID . "," . $target . ");");
			}
		}
	}
	
	protected function CreateSelectProfile()
	{
		if(!IPS_VariableProfileExists("DSJal.Selector"))
			IPS_CreateVariableProfile("DSJal.Selector", 1 /* Int */);
		IPS_SetVariableProfileIcon("DSJal.Selector", "Shutter");
		IPS_SetVariableProfileValues("DSJal.Selector", 0, 4, 0);
		IPS_SetVariableProfileAssociation("DSJal.Selector", 0, "Offen", "", -1);
		IPS_SetVariableProfileAssociation("DSJal.Selector", 1, "Geschlossen", "", -1);
		IPS_SetVariableProfileAssociation("DSJal.Selector", 2, "Ausblick", "", -1);
		IPS_SetVariableProfileAssociation("DSJal.Selector", 3, "Beschattung", "", -1);
		IPS_SetVariableProfileAssociation("DSJal.Selector", 4, "Sonnenschutz", "", -1);
	}
	
	protected function CreateWindProfile($type)
	{
		if(!IPS_VariableProfileExists("DSJal.Wind"))
			IPS_CreateVariableProfile("DSJal.Wind", 2 /* Float */);
		IPS_SetVariableProfileIcon("DSJal.Wind", "WindSpeed");
		if($type == 0 /* m/s */)
		{
			IPS_SetVariableProfileValues("DSJal.Wind", 0, 60, 0.5);
			IPS_SetVariableProfileText("DSJal.Wind", "", " m/s");
			IPS_SetVariableProfileDigits("DSJal.Wind", 2);
		}
		else if($type == 1 /* km/h */) 
		{
			IPS_SetVariableProfileValues("DSJal.Wind", 0, 200, 0.5);
			IPS_SetVariableProfileText("DSJal.Wind", "", " km/h");
			IPS_SetVariableProfileDigits("DSJal.Wind", 1);
		}
		else /* knoten */
		{
			IPS_SetVariableProfileValues("DSJal.Wind", 0, 100, 0.5);
			IPS_SetVariableProfileText("DSJal.Wind", "", " knoten");
			IPS_SetVariableProfileDigits("DSJal.Wind", 1);
		}
	}
	
	protected function CreateInstance($GUID, $name, $ident, $parentID = 0, $position = 0)
	{
		if(@IPS_GetObjectIDByIdent($ident, $parentID) === false)
		{
			$insID = IPS_CreateInstance($GUID);
		}
		else
		{
			$insID = IPS_GetObjectIDByIdent($ident, $parentID);
		}
		IPS_SetName($insID, $name);
		IPS_SetIdent($insID, $ident);
		if($parentID == 0)
			$parentID = $this->InstanceID;
		IPS_SetParent($insID, $parentID);
		IPS_SetPosition($insID, $position);
		
		return $insID;
	}
	
	protected function CreateVariable($type, $name, $ident, $parentID = 0, $position = 0, $initVal = 0, $profile = "", $actionID = "SetValue")
	{
		if(@IPS_GetObjectIDByIdent($ident, $parentID) === false)
		{
			$varID = IPS_CreateVariable($type);
			if($initVal != 0) //dont set value for 0
				SetValue($varID, $initVal);
		}
		else
		{
			$varID = IPS_GetObjectIDByIdent($ident, $parentID);
		}
		IPS_SetName($varID, $name);
		IPS_SetIdent($varID, $ident);
		if($parentID == 0)
			$parentID = $this->InstanceID;
		IPS_SetParent($varID, $parentID);
		IPS_SetPosition($varID, $position);
		if(IPS_VariableProfileExists($profile))
			IPS_SetVariableCustomProfile($varID,$profile);
		if($actionID == "SetValue")
			$actionID = $this->CreateSetValueScript($this->InstanceParentID);
		if($actionID > 9999)
			IPS_SetVariableCustomAction($varID,$actionID);
		
		return $varID;
	}
	
	protected function CreateCategory($name, $ident, $parentID = 0 , $position = 0)
	{
		if(@IPS_GetObjectIDByIdent($ident, $parentID) === false)
		{
			$catID = IPS_CreateCategory();
		}
		else
		{
			$catID = IPS_GetObjectIDByIdent($ident, $parentID);
		}
		//fix mixing up the targets
		if(IPS_GetObject($catID)['ObjectName'] != $name)
		{
			//determine if some room got deleted or added - if deleted and rearrange is needed
			if(strpos($ident, "raum") !== false)
			{
				$dataJSON = $this->ReadPropertyString("Raeume");
				$data = json_decode($dataJSON);
				$automatikIns = IPS_GetObjectIDByIdent("AutomatikIns", $this->InstanceParentID);
				if(count(IPS_GetChildrenIDs($automatikIns)) > count($data))
				{
					$roomNum = str_replace("Targetsraum","",$ident);
					$nextRoomIdent = "Targetsraum" . ($roomNum + 1);
					$currentTargetsFolder = IPS_GetObjectIDByIdent("$ident", $this->InstanceID);
					$nextTargetsFolder = IPS_GetObjectIDByIdent("$nextRoomIdent", $this->InstanceID);
					$currentArr = array("Jal" => IPS_GetObjectIDByIdent("Jalousie", $currentTargetsFolder),
										"Lam" => IPS_GetObjectIDByIdent("Lamellen", $currentTargetsFolder),
										"Swi" => IPS_GetObjectIDByIdent("Switch", $currentTargetsFolder),
										"StepStop" => IPS_GetObjectIDByIdent("StepStop", $currentTargetsFolder)
										);
					$nextArr = array("Jal" => IPS_GetObjectIDByIdent("Jalousie", $nextTargetsFolder),
										"Lam" => IPS_GetObjectIDByIdent("Lamellen", $nextTargetsFolder),
										"Swi" => IPS_GetObjectIDByIdent("Switch", $nextTargetsFolder),
										"StepStop" => IPS_GetObjectIDByIdent("StepStop", $nextTargetsFolder)
										);
					foreach($currentArr as $id => $value)
					{
						$currentLinks = IPS_GetChildrenIDs($value);
						$nextLinks = IPS_GetChildrenIDs($nextArr[$id]);
						foreach($currentLinks as $cnt => $link)
						{
							if(array_key_exists($cnt, $nextLinks))
							{ /* Change the Targets of the current Links to the new Links */
								$target = IPS_GetLink($nextLinks[$cnt])['TargetID'];
								$linkname = IPS_GetName($nextLinks[$cnt]);
								IPS_SetLinkTargetID($link, $target);
								IPS_SetName($link, $linkname);
							}
							else //if the current folder has more links than next folder
							{
									IPS_DeleteLink($link);
							}
						}
						if(count($currentLinks) < count($nextLinks)) //if the next folder has more links than previous folder
						{
							for($i = count($currentLinks); $i < count($nextLinks); $i++)
							{
								$linkident = IPS_GetObject($nextLinks[$i])['ObjectIdent'];
								$target = IPs_GetLink($nextLinks[$i])['TargetID'];
								$this->CreateLink($target, $linkident, $value, 0 /*pos*/);
							}
						}
					}
				}
			}				
		}
		IPS_SetName($catID, $name);
		IPS_SetIdent($catID, $ident);
		if($parentID == 0)
			$parentID = $this->InstanceID;
		IPS_SetParent($catID, $parentID);
		IPS_SetPosition($catID, $position);
		
		return $catID;
	}

	//////////////////////////////
	// Module Controlls /*MDC*/ //
	//////////////////////////////
	
	public function __construct($InstanceID) {
            //Never delete this line!
            parent::__construct($InstanceID);
			$this->dummyGUID = $this->GetModuleIDByName("Dummy Module");
        }
	
	public function Create() {
		//Never delete this line!
		parent::Create();
		
		//Register Properties
		if(@$this->RegisterPropertyString("Raeume") !== false)
		{
			$this->RegisterPropertyString("Raeume","");
			$this->RegisterPropertyInteger("DaysetVar",0);
			$this->RegisterPropertyInteger("WindVar",0);
			$this->RegisterPropertyInteger("Profile",0);
		}	
		//Define "Räume" Module as this->InstanceID
		IPS_SetIdent($this->InstanceID, "RaeumeIns");
		IPS_SetPosition($this->InstanceID, 3);
		
		//Create Profiles
		$this->CreateSelectProfile();
		$this->CreateWindProfile(0);
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
		
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
		//Define Instance Parent
		$this->InstanceParentID = IPS_GetParent($this->InstanceID);
		
		if($this->InstanceParentID != 0)
		{
			//reconfigure Wind Profile
			$type = $this->ReadPropertyInteger("Profile");
			$this->CreateWindProfile($type);
			
			IPS_SetIcon($this->InstanceID, "Jalousie");
			IPS_SetName($this->InstanceID, "Jalousie");
			
			//Create Automatin Dummy Module
			$dummyGUID = $this->GetModuleIDByName("Dummy Module");
			$this->CreateInstance($this->dummyGUID, "Automatik", "AutomatikIns", $this->InstanceParentID, 1);
			
			//Create Global Dummy Instance
			$globalIns = $this->CreateInstance($this->dummyGUID, "Global", "GlobalIns", $this->InstanceParentID, 1);
			
			if(@IPS_GetObjectIDByIdent("GlobalAutomatikVar", $this->InstanceParentID) !== false)
			{
				$vid = IPS_GetObjectIDByIdent("GlobalAutomatikVar", $this->InstanceParentID);
				IPS_DeleteVariable($vid);
			}
			//Create Global Automatic Switch
			$this->CreateVariable(0, "Globale Automatik", "GlobalAutomatikVar", $globalIns, 0, false, "~Switch", "SetValue");
			
			if(@IPS_GetObjectIDByIdent("MaximaleWindstaerkeVar", $this->InstanceParentID) !== false)
			{
				$vid = IPS_GetObjectIDByIdent("MaximaleWindstaerkeVar", $this->InstanceParentID);
				IPS_DeleteVariable($vid);
			}
			//Create Windstärke Limit
			$windProfileMaxValue = IPS_GetVariableProfile("DSJal.Wind")['MaxValue'];
			$this->CreateVariable(2, "Maximale Windstärke", "MaximaleWindstaerkeVar", $globalIns, -1, $windProfileMaxValue, "DSJal.Wind", "SetValue");
			
			//Create Einstellungen Folder
			if(@IPS_GetObjectIDByIdent("EinstellungenCat", $this->InstanceParentID) === false)
			{
				$cidEinstellungen = IPS_CreateCategory();
				IPS_SetName($cidEinstellungen, "Einstellungen");
				IPS_SetIdent($cidEinstellungen, "EinstellungenCat");
				IPS_SetParent($cidEinstellungen, $this->InstanceParentID);
			}
			else
			{
				$cidEinstellungen = IPS_GetObjectIDByIdent("EinstellungenCat", $this->InstanceParentID);
			}
			
			//Get Content of Table
			$dataJSON = $this->ReadPropertyString("Raeume");
			$data = json_decode($dataJSON);
			
			//Create Events Folder
			$EventCatID = $this->CreateCategory("Events", "EventsCat", $this->InstanceParentID, 9998);
			IPS_SetHidden($EventCatID, true);
				
			//Create Windstärke Sensor Event
			$vid = $this->ReadPropertyInteger("WindVar");
			$eid = $this->CreateEvent("Windstaerke" . "onchange", "Windstaerke" . "onchange", $EventCatID, 0, 1, $vid, "DSJal_refresh(". $this->InstanceID . "," . $vid . ");");
				
			//Create Windstärke Limit Event
			$globalIns = IPS_GetObjectIDByIdent("GlobalIns", $this->InstanceParentID);
			$vid = IPS_GetObjectIDByIdent("MaximaleWindstaerkeVar", $globalIns);
			$eid = $this->CreateEvent("MaximaleWindstaerke" . "onchange", "MaximaleWindstaerke" . "onchange", $EventCatID, 0, 1, $vid, "DSJal_refresh(". $this->InstanceID . "," . $vid . ");");
				
			//Create Objects for "Werte"
			$insID = $cidEinstellungen;
			$idJalousie = $this->CreateInstance($this->dummyGUID, "Jalousie", "JalousieIns", $insID, 30);
			$this->CreateVariable(0, "Auf", "OffenVar", $idJalousie, 0, true, "~Switch", "SetValue");
			$this->CreateVariable(0, "Zu", "GeschlossenVar", $idJalousie, 1, false, "~Switch", "SetValue");
			$idAusblick = $this->CreateInstance($this->dummyGUID, "Ausblick", "AusblickIns", $insID, 31);
			$this->CreateVariable(1, "Behang", "AusblickBehangVar", $idAusblick, 3, 0, "~Shutter", "SetValue");
			$this->CreateVariable(1, "Lamellen", "AusblickLamellenVar", $idAusblick, 4, 0, "~Shutter", "SetValue");
			$idBeschattung = $this->CreateInstance($this->dummyGUID, "Beschattung", "BeschattungIns", $insID, 32);
			$this->CreateVariable(1, "Behang", "BeschattungBehangVar", $idBeschattung, 6, 0, "~Shutter", "SetValue");
			$this->CreateVariable(1, "Lamellen", "BeschattungLamellenVar", $idBeschattung, 7, 0, "~Shutter", "SetValue");
			$idSonnenschutz = $this->CreateInstance($this->dummyGUID, "Sonnenschutz", "SonnenschutzIns", $insID, 33);
			$this->CreateVariable(1, "Behang", "SonnenschutzBehangVar", $idSonnenschutz, 9, 0, "~Shutter", "SetValue");
			$this->CreateVariable(1, "Lamellen", "SonnenschutzLamellenVar", $idSonnenschutz, 10, 0, "~Shutter", "SetValue");
			
			//Create onchange Events for all Variables in "Werte"
			$this->CreateEventForChildren($idJalousie, $EventCatID);
			$this->CreateEventForChildren($idAusblick, $EventCatID);
			$this->CreateEventForChildren($idBeschattung, $EventCatID);
			$this->CreateEventForChildren($idSonnenschutz, $EventCatID);
			
			//Create Objects for "Automatik"
			$insID = IPS_GetObjectIDByIdent("AutomatikIns", $this->InstanceParentID);
			IPS_SetPosition($insID, 2);
			foreach($data as $id => $content)
			{
				$vid = $this->CreateVariable(0, $content->Raumname, "raum$id", $insID, $id, false, "~Switch", "SetValue");
				$eid = $this->CreateEvent("Automatikraum$id" . "onchange", "Automatikraum$id" . "onchange", $EventCatID, 0, 4, $vid, "DSJal_refresh(". $this->InstanceID . "," . $vid . ");");
				IPS_SetEventTriggerValue($eid, true);
			}
			
			//Create Objects for "Tageszeiten"
			$insID = $cidEinstellungen;
			
			//Create Dayset Event
			$target = $this->ReadPropertyInteger("DaysetVar");
			$this->CreateEvent("Daysetonchange", "DaysetEvent", $EventCatID, 0, 1, $target, "DSJal_refresh(" . $this->InstanceID . "," . $target . ");");
			
				//Früh
				$idFrueh = $this->CreateInstance($this->dummyGUID, "Früh", "FruehIns", $insID, -1);
				//IPS_SetIcon($id, "Fog");
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Fruehraum$id", $idFrueh, $id, 0, "DSJal.Selector", "SetValue");
				}
				//Morgen
				$idMorgen = $this->CreateInstance($this->dummyGUID, "Morgen", "SonnenaufgangIns", $insID, count($data));
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Sonnenaufgangraum$id", $idMorgen, $id + 1 + count($data), 0, "DSJal.Selector", "SetValue");
				}
				//Tag
				$idTag = $this->CreateInstance($this->dummyGUID, "Tag", "TagIns", $insID, count($data) * 2 + 1);
				//IPS_SetIcon($id, "Sun");
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Tagraum$id", $idTag, $id + 1 + count($data) * 2 + 1, 0, "DSJal.Selector", "SetValue");
				}
				//Dämmerung
				$idDaemmerung = $this->CreateInstance($this->dummyGUID, "Dämmerung", "DaemmerungIns", $insID, count($data) * 3 + 2);
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Daemmerungraum$id", $idDaemmerung, $id + 1 + count($data) * 3 + 2, 0, "DSJal.Selector", "SetValue");
				}
				//Abend
				$idAbend = $this->CreateInstance($this->dummyGUID, "Abend", "AbendIns", $insID, count($data) * 4 + 3);
				//IPS_SetIcon($id, "Moon");
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Abendraum$id", $idAbend, $id + 1 + count($data) * 4 + 3, 0, "DSJal.Selector", "SetValue");
				}
				//Nacht
				$idNacht = $this->CreateInstance($this->dummyGUID, "Nacht", "NachtIns", $insID, count($data) * 5 + 4);
				//IPS_SetIcon($id, "Moon");
				foreach($data as $id => $content)
				{
					$this->CreateVariable(1, $content->Raumname, "Nachtraum$id", $idNacht, $id + 1 + count($data) * 5 + 4, 0, "DSJal.Selector", "SetValue");
				}
				
				//Create Events for All Tageszeiten
				foreach(IPS_GetChildrenIDs($insID) as $tageszeit)
				{
					$this->CreateEventForChildren($tageszeit, $EventCatID);
				}
				
			if(@IPS_GetObjectIDByIdent("GlobalRaeume", $this->InstanceParentID) !== false)
			{
				$vid = IPS_GetObjectIDByIdent("GlobalRaeume", $this->InstanceParentID);
				IPS_DeleteVariable($vid);
			}
			//Global Räume Selector		
			$vid = $this->CreateVariable(1, "Globale Räume" , "GlobalRaeume", $globalIns, 1 /*pos*/, 0 /*init Value */, "DSJal.Selector", "SetValue");
			//Global Räume Event	
			$eid = $this->CreateEvent("GlobRäume" . "OnChange", "GlobalRaeume" . "onchange", $EventCatID, 0, 0, $vid, "DSJal_refresh(" . $this->InstanceID . "," . $vid . ");");
			
			
			//Create Objects for "Räume"
			$insID = $this->InstanceID;
			foreach($data as $id => $content)
			{
				$catID = $this->CreateCategory($content->Raumname . ".Targets", "Targetsraum$id", $insID, $id - count($data));
				$this->CreateCategory("Jalousie", "Jalousie", $catID, 0);
				$this->CreateCategory("Lamellen", "Lamellen", $catID, 1);
				$this->CreateCategory("Switch", "Switch", $catID, 2);
				$this->CreateCategory("Step/Stop", "StepStop", $catID, 3);
				$vid = $this->CreateVariable(1, $content->Raumname, "raum$id", $insID, $id, 0, "DSJal.Selector", "SetValue");
				$this->CreateEvent($content->Raumname . "OnChange", "raum$id" . "onchange", $EventCatID, 0, 0, $vid, "DSJal_SetValue(" . $this->InstanceID . "," . "\"raum$id\");");
			}
			$insID = IPS_GetObjectIDByIdent("AutomatikIns", $this->InstanceParentID);
			if(count($data) < count(IPS_GetChildrenIDs($insID)))
			{
				$children = IPS_GetChildrenIDs($insID);
				$insID = $cidEinstellungen;
				for($i = count($data); $i < count($children); $i++)
				{
					$ident = IPS_GetObject($children[$i])['ObjectIdent'];
					IPS_DeleteVariable($children[$i]);
					$child = IPS_GetObjectIDByIdent($ident, $this->InstanceID);
					IPS_DeleteVariable($child);
					$cids = IPS_GetChildrenIDs($insID);
					foreach($cids as $childID)
					{
						$childrenIDs = IPS_GetChildrenIDs($childID);
						foreach($childrenIDs as $room)
						{
							$raumIdent = IPS_GetObject($room)['ObjectIdent'];
							if(strpos($raumIdent, $ident) !== false)
								IPS_DeleteVariable($room);
						}
					}
					//Delete unneccesary events
					$cids = IPS_GetChildrenIDs(IPS_GetObjectIDByIdent("EventsCat", $this->InstanceParentID));
					foreach($cids as $childrenIDs)
					{
						$raumIdent = IPS_GetObject($childrenIDs)['ObjectIdent'];
						if(strpos($raumIdent, $ident) !== false)
							IPS_DeleteEvent($childrenIDs);
					}
					//Delete unneccesary Targets
					$cids = @IPS_GetObjectIDByIdent("Targets$ident", $this->InstanceID);
					if($cids !== false)
						$this->DeleteObject($cids);
				}
			}
		}
	}
	
	private function SetValueByDevice($insID, $value)
	{
		if(gettype($value) == "boolean")
		{
			EIB_Switch($insID, $value);
		}
		else
		{
			EIB_DriveShutterValue($insID, $value);
		}
	}
	
	private function stopAktor($targetFolder)
	{
		$allTargetsFolder = IPS_GetParent($targetFolder);
		$stepStopFolder = IPS_GetObjectIDByIdent("StepStop", $allTargetsFolder);
		$stepStopLink = IPS_GetChildrenIDs($stepStopFolder)[0];
		$stepStopID = IPS_GetLink($stepStopLink)['TargetID'];
		if(IPS_HasChildren($stepStopID))
		{
			$stepStopID = IPS_GetChildrenIDs($stepStopID)[0];
		}
		$currentValue = GetValue($stepStopID);
		if($currentValue == true)
			$nextValue = false;
		else
			$nextValue = true;
		print_r($nextValue);
		$this->Set("$stepStopFolder" . 'StepStop' , $nextValue);
	}

	private function Set($targetFolder, $value)
	{
		$stepStop = false;
		if(strpos($targetFolder, "StepStop"))
		{
			$stepStop = true;
			$targetFolder = str_replace("StepStop", "", $targetFolder);
		}
		$targets = IPS_GetChildrenIDs($targetFolder);
		foreach($targets as $target) 
		{
			if(IPS_LinkExists($target)) //only allow links
			{
				$target = IPS_GetLink($target)['TargetID'];
				if(IPS_InstanceExists($target))
				{
					$insID = $target;
					$target = @IPS_GetChildrenIDs($target)[0];
				}
				if (IPS_VariableExists($target))
				{
					$o = IPS_GetObject($target);
					$v = IPS_GetVariable($target);
					$currentValue = GetValue($target);
					if(gettype($value) == "boolean" || $currentValue != $value)
					{	
						$switchValue = true;
					}
					else
					{
						$switchValue = false;
					}
					
					if($switchValue)
					{
						//Stop the current Action of the Actor
						if($stepStop == false)
							$this->stopAktor($targetFolder);

						if($v['VariableCustomAction'] > 0)
							$actionID = $v['VariableCustomAction'];
						else
							$actionID = $v['VariableAction'];
						
						//try changing the value by device-specific commands
						if($actionID < 10000)
						{
							if(@$insID != NULL)
								$this->SetValueByDevice($insID, $value);
							else
								SetValue($target, $value);
							//Skip this device if we do not have a proper id
							continue;
						}
							
						if(IPS_InstanceExists($actionID)) {
							IPS_RequestAction($actionID, $o['ObjectIdent'], $value);
						} else if(IPS_ScriptExists($actionID)) {
							echo IPS_RunScriptWaitEx($actionID, Array("VARIABLE" => $id, "VALUE" => $value, "SENDER" => "WebFront"));
						}	
					}
				}
				else
				{
					SetValueByDevice($insID, $value);
				}
			}
			else
			{
				throw new Exception('Only Links as Targets allowed');
			}
		}
	}
	
	////////////////////
	//public functions//
	////////////////////
	
	public function SetValue($ident)
	{
		$this->InstanceParentID = IPS_GetParent($this->InstanceID);
		$roomVar = IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		$WerteIns = IPS_GetObjectIDByIdent("EinstellungenCat", $this->InstanceParentID);
		
		//Get the Targets
		$targetsCatID = IPS_GetObjectIDByIdent("Targets$ident", $this->InstanceID);
		$targetsJal = IPS_GetObjectIDByIdent("Jalousie", $targetsCatID);
		$targetsLam = IPS_GetObjectIDByIdent("Lamellen", $targetsCatID);
		$targetsSwi = IPS_GetObjectIDByIdent("Switch", $targetsCatID);
		
		switch(GetValue($roomVar))
		{
			//CARE Set( xxx . "StepStop") to prevent double reset of the aktor
			case(0 /*Offen*/):
				//Get the Values
				$insID = IPS_GetObjectIDByIdent("JalousieIns", $WerteIns);
				$vid = IPS_GetObjectIDByIdent("OffenVar", $insID);
				$value = GetValue($vid);
				$this->Set($targetsSwi, $value);
				break;
			case(1 /*Geschlossen*/):
				//Get the Values
				$insID = IPS_GetObjectIDByIdent("JalousieIns", $WerteIns);
				$vid = IPS_GetObjectIDByIdent("GeschlossenVar", $insID);
				$value = GetValue($vid);
				$this->Set($targetsSwi, $value);
				break;
			case(2 /*Ausblick*/):
				//Get the Values
				//Behang
				$insID = IPS_GetObjectIDByIdent("AusblickIns", $WerteIns);
				$vid = IPS_GetObjectIDByIdent("AusblickBehangVar", $insID);
				$value = GetValue($vid);
				$this->Set($targetsJal, $value);
				//Lamellen
				$vid = IPS_GetObjectIDByIdent("AusblickLamellenVar", $insID);
				$value = GetValue($vid);
				$this->Set("$targetsLam" . "StepStop", $value);
				break;
			case(3 /*Beschattung*/):
				//Get the Values
				//Behang
				$insID = IPS_GetObjectIDByIdent("BeschattungIns", $WerteIns);
				$vid = IPS_GetObjectIDByIdent("BeschattungBehangVar", $insID);
				$value = GetValue($vid);
				$this->Set($targetsJal, $value);
				//Lamellen
				$vid = IPS_GetObjectIDByIdent("BeschattungLamellenVar", $insID);
				$value = GetValue($vid);
				$this->Set("$targetsLam" . "StepStop", $value);
				break;
			case(4 /*Sonnenschutz*/):
				//Get the Values
				//Behang
				$insID = IPS_GetObjectIDByIdent("SonnenschutzIns", $WerteIns);
				$vid = IPS_GetObjectIDByIdent("SonnenschutzBehangVar", $insID);
				$value = GetValue($vid);
				$this->Set($targetsJal, $value);
				//Lamellen
				$vid = IPS_GetObjectIDByIdent("SonnenschutzLamellenVar", $insID);
				$value = GetValue($vid);
				$this->Set("$targetsLam" . "StepStop", $value);
				break;
			default:
				echo "index not found: " . GetValue($roomVar);
		}
	}
	
	public function refresh($sender)
	{
		
		//Get Content of Table
		$dataJSON = $this->ReadPropertyString("Raeume");
		$data = json_decode($dataJSON);
		$this->InstanceParentID = IPS_GetParent($this->InstanceID);
		$automatikIns = IPS_GetObjectIDByIdent("AutomatikIns", $this->InstanceParentID);
		$tageszeitenIns = IPS_GetObjectIDByIdent("EinstellungenCat", $this->InstanceParentID);
		$daysetVar = $this->ReadPropertyInteger("DaysetVar");
		$senderIdent = @IPS_GetObject($sender)['ObjectIdent'];
		if($senderIdent != "GlobalRaeume")
		{
			foreach($data as $id => $content)
			{
				switch(GetValue($daysetVar))
				{
					case(1 /*Früh*/):
						$insID = IPS_GetObjectIDByIdent("FruehIns", $tageszeitenIns);
						$vid = IPS_GetObjectIDByIdent("Fruehraum$id", $insID);
						break;
					case(2 /*Sonnenaufgang (Morgen) */):
						$insID = IPS_GetObjectIDByIdent("SonnenaufgangIns", $tageszeitenIns);
						$vid = IPS_GetObjectIDByIdent("Sonnenaufgangraum$id", $insID);
						break;
					case(3 /*Tag*/):
						$insID = IPS_GetObjectIDByIdent("TagIns", $tageszeitenIns);
						$vid = IPS_GetObjectIDByIdent("Tagraum$id", $insID);
						break;
					case(4 /*Dämmerung*/):
						$insID = IPS_GetObjectIDByIdent("DaemmerungIns", $tageszeitenIns);
						$vid = IPS_GetObjectIDByIdent("Daemmerungraum$id", $insID);
						break;
					case(5 /*Abend*/):
						$insID = IPS_GetObjectIDByIdent("AbendIns", $tageszeitenIns);
						$vid = IPS_GetObjectIDByIdent("Abendraum$id", $insID);
						break;
					case(6 /*Nacht*/):
						$insID = IPS_GetObjectIDByIdent("NachtIns", $tageszeitenIns);
						$vid = IPS_GetObjectIDByIdent("Nachtraum$id", $insID);
						break;
				}
				$raumID = IPS_GetObjectIDByIdent("raum$id", $this->InstanceID);
				$automatikRaumID = IPS_GetObjectIDByIdent("raum$id", $automatikIns);
				$automatik = GetValue($automatikRaumID);
				$globalIns = IPS_GetObjectIDByIdent("GlobalIns", $this->InstanceParentID);
				$automatikGlobalID = IPS_GetObjectIDByIdent("GlobalAutomatikVar", $globalIns);
				$automatikGlobal = GetValue($automatikGlobalID);
				$vIdent = @IPS_GetObject($sender)['ObjectIdent'];
				//if the Velocity of the Wind is too highlight_file
				$WindVar = $this->ReadPropertyInteger("WindVar");
				$globalIns = IPS_GetObjectIDByIdent("GlobalIns", $this->InstanceParentID);
				$WindLimitVar = IPS_GetObjectIDByIdent("MaximaleWindstaerkeVar", $globalIns);
				if($sender == $WindVar || $sender == $WindLimitVar)
				{
					$WindLimitValue = GetValue($WindLimitVar);
					$WindValue = GetValue($WindVar);
					if($WindLimitValue < $WindValue)
					{
						SetValue($automatikGlobalID, false); //turn off Automation
						SetValue($raumID, 0); //Open All Jalousien
					}
				}
				if($automatikGlobal && $automatik && ($sender == $daysetVar /*sender = dayset*/ || strpos($vIdent, "raum") !== false /*sender = Automatik || Tageszeiten*/))
				{
					$value = GetValue($vid);
					SetValue($raumID, $value);
				}
				else
				{
					$o = @IPS_GetObject($sender);
					$i = $o['ObjectIdent'];
					if(strpos($i, "Offen") !== false)
					{
						if(GetValue($raumID) == 0)
							SetValue($raumID, 0);
					} else if(strpos($i, "Geschlossen") !== false)
					{
						if(GetValue($raumID) == 1)
							SetValue($raumID, 1);
					} else if(strpos($i, "Ausblick") !== false)
					{
						if(GetValue($raumID) == 2)
							SetValue($raumID, 2);
					} else if(strpos($i, "Beschattung") !== false)
					{
						if(GetValue($raumID) == 3)
							SetValue($raumID, 3);
					} else if(strpos($i, "Sonnenschutz") !== false)
					{
						if(GetValue($raumID) == 4)
							SetValue($raumID, 4);
					}
				}
			}
		} else //sender = Global Rooms Selector var
		{
			foreach($data as $id => $content)
			{
				$vid = IPS_GetObjectIDByIdent("raum$id", $this->InstanceID);
				$value = GetValue($sender);
				SetValue($vid, $value);
			}
		}
	}
}
?>