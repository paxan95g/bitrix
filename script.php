<?php
	if (CModule::IncludeModule("iblock")) {
		// ID инфоблока
		$targetBlockID = 12;
		// Путь к файлу с данными
		$filePath = "test.csv";		


		// Считываем данные из файла
		$handle = fopen($filePath, "r") or exit("Невозможно открыть файл: $filePath");
		$dataFirst = fgetcsv($handle,0,";");

		
		// Сохраняем данные из файла в массив
		while (!feof($handle)) {
		    $line = fgets($handle);
		    $data = explode(";", $line);

		    if (count($data) > 1) {
		    	$dFile[] = array_combine($dataFirst, $data);
		    }
		}

		// Сохраняем данные из инфоблока в массив		
		$my_block = CIBlockElement::GetList (			
		Array("ID" => "ASC"),
		Array("IBLOCK_ID" => $targetBlockID),
		false,
		false,			
		Array(				
			'XML_ID',
			'NAME', 
			'PREVIEW_TEXT', 
			'DETAIL_TEXT', 
			'PROPERTY_ATT_PROP1',
			'PROPERTY_ATT_PROP2',
			'ID'
		)
		);
		while($ar_fields = $my_block->GetNext()) {	
			$dSite[] = $ar_fields;									
		}
		
		
		$dataFromFileCount = count($dFile); 	// Количество записей в файле
		$dataFromSiteCount = count($dSite);		// Количество записей в инфоблоке

		$lineAdded = 0;							// Количество добавленных записей 
		$lineChanged = 0; 						// Количество обновленных записей 
		$lineDeleted = 0;						// Количество удаленных записей
		$lineCount = 0;							// Всего записей в инфоблоке			
		


		for ($i=0; $i < $dataFromFileCount; $i++) {
			$toAdd = true;
					
			for ($j=0; $j < $dataFromSiteCount; $j++) { 
				
				// Если запись из файла найдена в инфоблоке
				if ($dFile[$i]['id'] == $dSite[$j]['XML_ID']) {
					$toAdd = false;						
					// Проверяем, актуальность данных этой записи в инфоблоке 
					if (
						$dFile[$i]['name'] != $dSite[$j]['NAME'] ||
						$dFile[$i]['preview_text'] != $dSite[$j]['PREVIEW_TEXT'] ||
						$dFile[$i]['detail_text'] != $dSite[$j]['DETAIL_TEXT'] ||
						$dFile[$i]['prop1'] != $dSite[$j]['PROPERTY_ATT_PROP1_VALUE'] ||
						$dFile[$i]['prop2'] != $dSite[$j]['PROPERTY_ATT_PROP2_VALUE']
					) {
						// Обновляем данные в инфоблоке (если они не актуальны)
						$el = new CIBlockElement;
						$PROP = array();
						$PROP['ATT_PROP1'] = $dFile[$i]['prop1']; 
						$PROP['ATT_PROP2'] = $dFile[$i]['prop2'];

						$arLoadProductArray = Array(		  
							"NAME" => $dFile[$i]['name'],
							'PREVIEW_TEXT' => $dFile[$i]['preview_text'], 
							'DETAIL_TEXT' => $dFile[$i]['detail_text'],
							"PROPERTY_VALUES" => $PROP
						  );
							
						$res = $el->Update($dSite[$j]['ID'], $arLoadProductArray);
						$lineChanged++;
					}
					$lineCount++;
					break;
				}
			}
			// Если запись из файла не найдена в инфоблоке
			if ($toAdd) {
				// Добавляем элемент в инфоблок
			    $arFields = array(   
				    "IBLOCK_ID" => $targetBlockID,
				    "XML_ID" => $dFile[$i]['id'],
				    "NAME" => $dFile[$i]['name'],
				    "PREVIEW_TEXT" => $dFile[$i]['preview_text'],
				    "DETAIL_TEXT" => $dFile[$i]['detail_text'],
				    "PROPERTY_VALUES" => array(
					   "ATT_PROP1" => $dFile[$i]['prop1'],
					   "ATT_PROP2" => $dFile[$i]['prop2']
					   )
				);
				$oElement = new CIBlockElement();
				$idElement = $oElement->Add($arFields, false, false, false);
				$lineAdded++;
				$lineCount++;
			}
		}

	
		// Удаляем записи в инфоблоке, если их нету в файле
		for ($i=0; $i < $dataFromSiteCount; $i++) {
		$toDetele = true;		
			for ($j=0; $j < $dataFromFileCount; $j++) {					
				if ($dSite[$i]['XML_ID'] == $dFile[$j]['id']) {
					$toDetele = false;						
					break;
				}
			}				
			if ($toDetele) {					
				// Удаляем элемент из инфоблока			
				CIBlockElement::Delete($dSite[$i]['ID']);
				$lineDeleted++;					
			}
		}

		// Выводим статистику			
		echo "<br>Добавлено записей: $lineAdded";
		echo "<br>Изменено записей: $lineChanged";
		echo "<br>Удалено записей: $lineDeleted";
		echo "<br>Всего записей: $lineCount";		
	}
?>