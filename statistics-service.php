<?php
namespace SplitWit\StatisticsService;
// Report all errors
error_reporting(E_ALL);
ini_set("display_errors", 1);

if(isset($_GET['method'])){
	
	$method = $_GET['method'];
	$service = new StatisticsService();
	switch ($method) {
	    case "determineSignificance":
		    $service -> determineSignificance();
		   	

		    $response['significant'] = $service->significant;
		    $response['control_conversion_rate'] = $service->control_conversion_rate;
		    $response['variation_conversion_rate'] = $service->variation_conversion_rate;
		    $response['uptick'] = $service->uptick;
		    // $response['z_score'] = $service->z_score;
		    // $response['p_value'] = $service->p_value;
		    // $response['spread'] = $service->spread;

		    echo json_encode($response);

		    break;

	    default:
	    	echo "Method not found: " . $method;
	    	// die();
	}

}

class StatisticsService
{
	public $significant;
	public $control_conversion_rate;
	public $variation_conversion_rate;
	public $z_score;
	public $p_value;
	public $spread;
	public $uptick;

	public function __construct() {
		 
		 
	}

	
	public function calculate_p_value($x){
		// A p-value of 5% (0.05) or lower is often considered to be statistically significant.

		//these values come from a book - Understanding Probability: Chance Rules in Everyday Life
		$d1=0.0498673470;
		$d2=0.0211410061;
		$d3=0.0032776263;
		$d4=0.0000380036;
		$d5=0.0000488906;
		$d6=0.0000053830;
		$a=abs($x);
		$t=1.0+$a*($d1+$a*($d2+$a*($d3+$a*($d4+$a*($d5+$a*$d6)))));
		$t*=$t;
		$t*=$t;
		$t*=$t;
		$t*=$t;
		$t=1.0/($t+$t);
		if($x>=0){
			$t=1-$t;
		}

		if($t > 0.5){
			$t = 1 - $t;
		}

		$t = round($t*1000)/1000;

		return $t;
	}

	
	
	public function standardErrorOfDifference($control_conversion_rate, $variation_conversion_rate, $control_visitors, $variation_visitors){
		

		$standard_error_1 = $control_conversion_rate * (1-$control_conversion_rate) / $control_visitors;
		$standard_error_2 = $variation_conversion_rate * (1-$variation_conversion_rate) / $variation_visitors;
		$x = $standard_error_1 + $standard_error_2;

		return sqrt($x);

	}
	

	public function determineUptick($control_conversion_rate, $variation_conversion_rate){
		$uptick = 0;
		if($control_conversion_rate > $variation_conversion_rate){
			$uptick = (($control_conversion_rate - $variation_conversion_rate) / ($variation_conversion_rate)) * 100;
			 
		}

		if($control_conversion_rate < $variation_conversion_rate){
			$uptick = (($variation_conversion_rate - $control_conversion_rate) / ($control_conversion_rate)) * 100;
			 
		}
		return $uptick;
	}

	public function determineSignificance(){
		$data = $_GET;
		$requiredKeys = ['controlVisitors', 'variationVisitors', 'controlHits', 'variationHits'];
        foreach ($requiredKeys as $required) {
            if (!in_array($required, array_keys($data))) {
                echo "The provided request data is missing one of the following keys: " . implode(', ', $requiredKeys);
                die();
            }
        }

        if(isset($_GET['controlVisitors'])){
        	$control_visitors = $_GET['controlVisitors'];
        }
        if(isset($_GET['variationVisitors'])){
        	$variation_visitors = $_GET['variationVisitors'];
        }
        if(isset($_GET['controlHits'])){
        	$control_hits = $_GET['controlHits'];
        }
        if(isset($_GET['variationHits'])){
        	$variation_hits = $_GET['variationHits'];
        }
        
 		
 		//this is the conversion rate
 		$notEnoughData = false;
 		if($control_visitors == 0){
		   	$this->control_conversion_rate = 0;
 			$notEnoughData = true;
 		}

 		if($variation_visitors == 0){
			$this->variation_conversion_rate = 0;
 			$notEnoughData = true;
 		}

 		if($notEnoughData){
	    	$this->significant = "false";
			$this->uptick = 0;
			return;
 		}

		$control_conversion_rate = $control_hits/$control_visitors;
		$variation_conversion_rate = $variation_hits/$variation_visitors;

		//things break if the conversion rate is more than 100%
		
		$standard_error = sqrt( ($control_conversion_rate*(1-$control_conversion_rate)/$control_visitors)+($variation_conversion_rate*(1-$variation_conversion_rate)/$variation_visitors) );
		
		// $standard_error = $this->standardErrorOfDifference($control_conversion_rate, $variation_conversion_rate, $control_visitors, $variation_visitors);

		//z-score (also called a standard score) gives you an idea of how far from the mean a data point is.
		$z_score = ($variation_conversion_rate-$control_conversion_rate)/$standard_error;
		$p_value = $this->calculate_p_value($z_score);
		

		$significant = false;
		if($p_value<0.05){
			$significant = true;
		}else{
			$significant = false;
		}


		if($significant){
	    	$this->significant = "true";
	    }else{
	    	$this->significant = "false";
	    }

	    $this->control_conversion_rate = $control_conversion_rate;
		$this->variation_conversion_rate = $variation_conversion_rate;
		$this->uptick = $this->determineUptick($control_conversion_rate, $variation_conversion_rate);
		// $this->p_value = $p_value;
		// $this->z_score = $z_score;		    


	}
	
}


?>
