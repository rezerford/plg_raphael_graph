<?php


// No direct access.
defined('_JEXEC') or die;

class plgContentRgraph extends JPlugin
{
	
	public $i;
	
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') {
			return true;
		}

		if (is_object($row)) {
			return $this->_buildGraph($row->text, $params);
		}
		return $this->_buildGraph($row, $params);
	}

	protected function _buildGraph(&$text, &$params)
	{
		$this->i = 0;
		$document = JFactory::getDocument();
		$document->addScript('https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js');
		$document->addScript(JURI::root().'plugins/content/rgraph/js/raphael.js');
		$document->addScript(JURI::root().'plugins/content/rgraph/js/g.raphael.js');		
				
		if (JString::strpos($text, '{rgraph') === false) {
			return true;
		}
				
		$pattern = '/\{rgraph\s*([^}]*)\}/';
		if(preg_match_all($pattern, $text, $attr)) {
			
			$replacement = '';
			$attrs = $attr[1];
			$parts = $attr[0];
			
			$types = array();
			if(count($attrs)){
				foreach($attrs as $attr){
					$type_pattern = '/type=(\"|\')([^"|\']*)(\"|\')/';
					if(preg_match($type_pattern, $attr, $type)){
						$types[] = trim($type[2]);
					}
				}
			}
			
			if(in_array('line', $types )){
				$document->addScript(JURI::root().'plugins/content/rgraph/js/mypopup.js');
				$document->addScript(JURI::root().'plugins/content/rgraph/js/line.js');
			}
			if(in_array('pie', $types)){
				$document->addScript(JURI::root().'plugins/content/rgraph/js/piechart.js');
			}
			if(in_array('bar', $types )){
				$document->addScript(JURI::root().'plugins/content/rgraph/js/barchart.js');
			}
			if(in_array('dot', $types )){
				$document->addScript(JURI::root().'plugins/content/rgraph/js/dotchart.js');
			}
			
			if(count($attrs)){
				foreach($attrs as $attr){
					$type_pattern = '/type=(\"|\')([^"|\']*)(\"|\')/';
					if(preg_match($type_pattern, $attr, $type)){
						
						if(trim($type[2]) == 'line'){
							
							$colorlabel = $this->params->def('colorlabel', '#ffffff');
							$colorhue = $this->params->def('colorhue', 0.6);
							$colorgrid = $this->params->def('colorgrid', '#000');
							$linewidth = $this->params->def('linewidth', '600');
							$lineheight = $this->params->def('lineheight', '250');
							$options = array($colorlabel, $colorhue, $colorgrid, $linewidth, $lineheight);
							
							$data_pattern = '/data=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($data_pattern, $attr, $data)){
								$data = trim($data[2]);
								$data = ($data) ? explode(',', $data) : array();
							}
							
							$label_pattern = '/label=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($label_pattern, $attr, $label)){
								$label = trim($label[2]);
								$label = ($label) ? explode(',', $label) : array();
							}
							
							$markerx = '';
							$markerx_pattern = '/markerx=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($markerx_pattern, $attr, $markerx)){
								$markerx = trim($markerx[2]);
							}
							
							$markery = '';
							$markery_pattern = '/markery=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($markery_pattern, $attr, $markery)){
								$markery = trim($markery[2]);
							}
							
							array_push($options, $markerx);
							array_push($options, $markery);
									
							$replacement = $this->_getLineGraph($data, $label, $options);
							
						} elseif(trim($type[2]) == 'pie'){
							
							$piewidth = $this->params->def('piewidth', '800');
							$pieheight = $this->params->def('pieheight', '350');
							$cx = $this->params->def('cx', '100');
							$cy = $this->params->def('cy', '100');
							$r = $this->params->def('r', '100');
							$options = array($piewidth, $pieheight, $cx, $cy, $r);
							
							$data_pattern = '/data=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($data_pattern, $attr, $data)){
								$data = trim($data[2]);
								$data = ($data) ? explode(',', $data) : array();
							}
							
							$label_pattern = '/label=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($label_pattern, $attr, $label)){
								$label = trim($label[2]);
								$label = ($label) ? explode(',', $label) : array();
							}
							
							$arr = array();
							if(count($data) && count($label) && count($data) == count($label)){
								foreach($data as $z => $d){
									$obj = new stdClass;
									$obj->percent = $d;
									$obj->label = $label[$z];
									$arr[] = $obj;
								}
							}
							
							if(count($arr)){
								$replacement = $this->_getPieGraph($arr, $options);
							}
						} elseif(trim($type[2]) == 'bar'){
							$data_pattern = '/data=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($data_pattern, $attr, $data)){
								$data = trim($data[2]);
								$data = ($data) ? explode(',', $data) : array();
							}
							
							$options = array();
							$barwidth = $this->params->def('barwidth', '300');
							$barheight = $this->params->def('barheight', '220');
							$bx = $this->params->def('bx', '10');
							$by = $this->params->def('by', '10');
							$barcolor = $this->params->def('barcolor', '#000077');
							$options[] = $barwidth;
							$options[] = $barheight;
							$options[] = $bx;
							$options[] = $by;
							$options[] = $barcolor;
							
							$replacement = $this->_getBarChart($data, $options);
						} elseif(trim($type[2]) == 'dot'){
							
							$options = array();
							$dotwidth = $this->params->def('dotwidth', '620');
							$dotheight = $this->params->def('dotheight', '260');
							$dx = $this->params->def('dx', '10');
							$dy = $this->params->def('dy', '10');
							$options[] = $dotwidth;
							$options[] = $dotheight;
							$options[] = $dx;
							$options[] = $dy;
							
							$xs_pattern = '/xs=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($xs_pattern, $attr, $xs)){
								$xs = trim($xs[2]);
								$xs = ($xs) ? explode(',', $xs) : array();
							}
							$ys_pattern = '/ys=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($ys_pattern, $attr, $ys)){
								$ys = trim($ys[2]);
								$ys = ($ys) ? explode(',', $ys) : array();
							}
							$data_pattern = '/data=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($data_pattern, $attr, $data)){
								$data = trim($data[2]);
								$data = ($data) ? explode(',', $data) : array();
							}
							$axisx_pattern = '/axisx=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($axisx_pattern, $attr, $axisx)){
								$axisx = trim($axisx[2]);
								$axisx = ($axisx) ? explode(',', $axisx) : array();
							}
							$axisy_pattern = '/axisy=(\"|\')([^"|\']*)(\"|\')/';
							if(preg_match($axisy_pattern, $attr, $axisy)){
								$axisy = trim($axisy[2]);
								$axisy = ($axisy) ? explode(',', $axisy) : array();
							}
							
							$replacement = $this->_getDotChart($xs, $ys, $data, $axisx, $axisy, $options);
						}
					}
					
					if(isset($parts[$this->i])){
						$text = str_replace($parts[$this->i], $replacement, $text );
					}
					$this->i++;
					
				}
			}			
		}

		
		return true;
	}
	
	protected function _getLineGraph($data, $label, $options){
		ob_start();
		?>
		<table id="data<?php echo $this->i;?>">
            <tfoot>
                <tr>
				<?php if(count($label)){
						foreach($label as $l):
				?>
				<th><?php echo $l;?></th>
				<?php endforeach;
				}
				?>
				</tr>
            </tfoot>
            <tbody>
                <tr>
				<?php if(count($data)){
						foreach($data as $d):
				?>
				<td><?php echo $d;?></td>
				<?php endforeach;
				}
				?>
				</tr>
            </tbody>
        </table>
		<div id="holder<?php echo $this->i;?>"></div>			
		<script type="text/javascript">
		
		jQuery(function () {
			jQuery("#data<?php echo $this->i;?>").css({
				position: "absolute",
				left: "-9999em",
				top: "-9999em"
			});
		});
	
		var n = <?php echo $this->i;?>;
		var options = new Array();
		<?php foreach($options as $option):?>
		options.push('<?php echo $option;?>');
		<?php endforeach;?>
		// Grab the data
		var labels = [],
			data = [];
		jQuery("#data<?php echo $this->i;?> tfoot th").each(function () {
			labels.push(jQuery(this).html());
		});
		jQuery("#data<?php echo $this->i;?> tbody td").each(function () {
			data.push(jQuery(this).html());
		});
		
		drawLine(data, labels, options, n);			
		</script>
		
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}
	
	protected function _getPieGraph($obj, $options){
		ob_start();
		?>
		<table id="data<?php echo $this->i;?>">
            <tbody>
				<?php if(count($obj)){
				foreach($obj as $data):
				?>
                <tr>
                    <th scope="row"><?php echo $data->label;?></th>
                    <td><?php echo $data->percent;?></td>
                </tr>
				<?php endforeach;
				}
				?>
            </tbody>
        </table>
		<div id="holder<?php echo $this->i;?>"></div>
		
		<script type="text/javascript">
		
		var values = [],
			labels = [];
		jQuery("#data<?php echo $this->i;?> tr").each(function () {
			values.push(parseInt(jQuery("td", this).text(), 10));
			labels.push(jQuery("th", this).text());
		});
		jQuery("#data<?php echo $this->i;?>").hide();
		Raphael("holder<?php echo $this->i;?>", <?php echo $options[0];?>, <?php echo $options[1];?>).pieChart(<?php echo $options[2];?>, <?php echo $options[3];?>, <?php echo $options[4];?>, values, labels, "#fff");
	
		</script>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}
	
	protected function _getBarChart($data, $options){
		ob_start();
		?>
		<div id="holder<?php echo $this->i;?>"></div>
		<script type="text/javascript">
		
		var r = Raphael("holder<?php echo $this->i;?>"),
		fin = function () {
			this.flag = r.popup(this.bar.x, this.bar.y, this.bar.value || "0").show();
		},
		fout = function () {
			this.flag.animate({opacity: 0}, 300, function () {this.remove();});
		},
		fin2 = function () {
			var y = [], res = [];
			for (var i = this.bars.length; i--;) {
				y.push(this.bars[i].y);
				res.push(this.bars[i].value || "0");
			}
			this.flag = r.popup(this.bars[0].x, Math.min.apply(Math, y), res.join(", ")).show();
		},
		fout2 = function () {
			this.flag.animate({opacity: 0}, 300, function () {this.remove();});
		},
		
		txtattr = { font: "12px sans-serif" };
		var data_arr = new Array();
		<?php if(count($data)):?>
			<?php foreach($data as $d):?>
		data_arr.push(<?php echo $d;?>);
			<?php endforeach;?>
		<?php endif; ?>
		
		r.barchart(<?php echo $options[2];?>, <?php echo $options[3];?>, <?php echo $options[0];?>, <?php echo $options[1];?>, [data_arr], {colors:['<?php echo $options[4];?>']}).hover(fin, fout);
		
		</script>
		<?
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}
	
	protected function _getDotChart($xs, $ys, $data, $axisx, $axisy, $options){
		ob_start();
		?>
		<div id="holder<?php echo $this->i;?>"></div>
		<script type="text/javascript">
			var r = Raphael("holder<?php echo $this->i;?>"),
			xs = [],
			ys = [],
			data = [],
			axisy = [],
			axisx = [];
			
			<?php if(count($xs)):?>
			<?php foreach($xs as $d):?>
			xs.push(<?php echo $d;?>);
			<?php endforeach;?>
			<?php endif; ?>
			
			<?php if(count($ys)):?>
			<?php foreach($ys as $d):?>
			ys.push(<?php echo $d;?>);
			<?php endforeach;?>
			<?php endif; ?>
			
			<?php if(count($data)):?>
			<?php foreach($data as $d):?>
			data.push(<?php echo $d;?>);
			<?php endforeach;?>
			<?php endif; ?>
			
			<?php if(count($axisy)):?>
			<?php foreach($axisy as $d):?>
			axisy.push("<?php echo $d;?>");
			<?php endforeach;?>
			<?php endif; ?>
			
			<?php if(count($axisx)):?>
			<?php foreach($axisx as $d):?>
			axisx.push("<?php echo $d;?>");
			<?php endforeach;?>
			<?php endif; ?>
			
			r.dotchart(<?php echo $options[2];?>, <?php echo $options[3];?>, <?php echo $options[0];?>, <?php echo $options[1];?>, xs, ys, data, {symbol: "o", max: 10, heat: true, axis: "0 0 1 1", axisxstep: 23, axisystep: 6, axisxlabels: axisx, axisxtype: " ", axisytype: " ", axisylabels: axisy}).hover(function () {
				this.marker = this.marker || r.tag(this.x, this.y, this.value, 0, this.r + 2).insertBefore(this);
				this.marker.show();
			}, function () {
				this.marker && this.marker.hide();
			}); 
		</script>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}
}
