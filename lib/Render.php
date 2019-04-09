<?php
namespace Pnet\Bus;


class Render {


	public static function generateResultTable($selectinfo) {

		$table_t = "<table>
			<thead><tr>%s</tr></thead>
			<tbody>
				%s
			</tbody>
			</table>";
		$row_t = "<tr>%s</tr>";

		$rows = [];
		//var_dump($selectinfo);
		
		if(!empty($selectinfo)) {
			foreach ($selectinfo as $idx => $value_row) {
				$row = array_map(function($value) {
					return "<td>$value</td>";
				}, array_values($value_row));

				$rows[] = sprintf($row_t, implode("", $row));
			}

			$fieldlist = array_keys($selectinfo[0]);
			$headline = array_map(function($value) {
				return "<th>$value</th>";
			}, $fieldlist);
		} else {
			$headline[] = "empty result";
			$rows = [];
		}

		$table = sprintf($table_t, 
			implode("", $headline), 
			implode("\n", $rows)
		);

		return $table;
	}

	public static function mapTypeToIcon($type) {
		switch ($type) {
			case 'CH_Main_Automation':
				return 'certificate';
				// lamp
			case 'CH_ShutterWithoutPosition_Automation':
				return 'sort';
			case 'CH_Dimmer_Automation':
				return 'sort-by-attributes-alt';
				// brightness-increase
				// signal
			case 'CH_Scene':
				return 'tasks';
			case 'CH_Clima':
				return 'dashboard';
			default:
				return 'asterisk';
		}
	}

	public static function generateElement($element, $border = true) {
		if(!empty($border)) {
			$div_t = '
			<div class="col-xs-4 col-sm-3 col-md-2 col-lg-1 element">
				<a href="#" onclick="return false" class="btn btn-%s btn-xs btn-multiline" data-element-id="%d" data-status-onoff-id="%d" data-status-onoff-data="%d"
					data-status-value-id="%d" data-status-value-data="%d" >
				  <span class="glyphicon glyphicon-%s"></span> %s
				</a>
			</div>';
		} else {
			$div_t = '
				<a href="#" onclick="return false" class="btn btn-%s btn-xs btn-multiline" data-element-id="%d" data-status-onoff-id="%d" data-status-onoff-data="%d"
					data-status-value-id="%d" data-status-value-data="%d" >
				  <span class="glyphicon glyphicon-%s"></span> %s
				</a>';
		}
			
		/*
			button-color (on/off),
			elementid, 
			statusid (on/off), value (on/off),
			statusid (value), value (value),
			icon, name
		*/

		/*
			<span class="glyphicon glyphicon-%s></span>

			<span class="icon-input-btn"><span class="glyphicon glyphicon-search"></span> <input type="submit" class="btn btn-default" value="Search"></span>


			<div class="inner">
				<button type="button" value="element_%d" class="btn btn-%s">%s <span class="badge">%s</span></button> 
			</div>
		*/

		$buttonColorClass = 'default';
		if($element->isOn() === '1') {
			$buttonColorClass = 'success';
		}

		return sprintf($div_t,
			$buttonColorClass,
			$element->id,
			$element->getStatusId("on/off"),
			$element->getStatusData("on/off"),
			$element->getStatusId("value"),
			$element->getStatusData("value"),
			self::mapTypeToIcon($element->values_type),
			$element->getName()
		);

	}

	public static function generateElementList($elements) {

		$row_r = '<div class="row">%s</div>
		';
 
		$result = '';
		$divs = [];

		if(!empty($elements)) {
			$count = 0;
			foreach ($elements as $idx => $element) {

				$divs[] = self::generateElement($element);

				if(++$count % 12 === 0) {
					$result .= sprintf($row_r, implode("", $divs));
					$divs = [];
				}
			}
		}
		if(!empty($divs)) {
			$result .= sprintf($row_r, implode("", $divs));
		}

		return $result;
	}
}