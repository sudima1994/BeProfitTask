<?php
include_once 'connect.php';
global $db;

abstract class FinancialStatus
{
    const paid = 'paid';
    const partially_paid = 'partially_paid';
    const partially_refunded = 'partially_refunded';
    const pending = 'pending';
    const refunded = 'refunded';
}

abstract class FulfillmentStatus
{
    const fulfilled = 'fulfilled';
    const unfulfilled = 'unfulfilled';
}



// First we fetch the data from the given endpoint using the given credentials.
$data = fetchData('https://www.become.co/api/rest/test/', 'tzinch', 'r#eD21mA%gNU');
if($data){
    $keys = array_keys($data[0]);
    // Create a new table for the data if not already created.
    $db->createTable('orders', $keys);
    // Save the data to the DB.
    // saveDataToDB($data, 'orders');
    $net_sales = getNetSales();
    echo "The net sales of the provided orders is: ". number_format($net_sales, 2, '.', '') . "<br>";
    $production_costs = getProductionCosts();
    echo "The production costs of the provided orders is: ". number_format($production_costs, 2, '.', '') . "<br>";
    $gross_profit = $net_sales - $production_costs;
    echo "The gross profits of the provided orders is: ". number_format($gross_profit, 2, '.', '') . "<br>";
    if($net_sales !== 0) {
        $gross_margin = ($gross_profit/$net_sales)*100;
        echo "The gross margins of the provided orders is: ". number_format($gross_margin, 2, '.', '') . "%";
    } else {
        echo "The gross margins of the provided orders is: Undefined";
    }


}

/**
 * Fetch data from a given endpoint using the GET method.
 * @param $path = the url path
 * @param $username = the username as part of the credentials for the authentication.
 * @param $password = the password as part of the credentials for the authentication.
 * @return - the response data or NULL if no response was given.
 */
function fetchData($path, $username, $password) {
    $context = stream_context_create(array(
        'http' => array(
            'header'  => "Authorization: Basic " . base64_encode("$username:$password")
        )
    ));
    // Parse the response string into a JSON array.
    $respone =  json_decode(file_get_contents($path, false, $context), true);
    // Check if the response was successfull and return the data part.
    if($respone['success']){
        return $respone['data'];
    }
    return NULL;
}

/**
 * Save the data to the connected database.
 * @param $data = the data that will be saved.
 * @param $table_name = the table in the database that the data will be added to.
 */
function saveDataToDB($data, $table_name){
    global $db;
    
    //The properties and their values are arranged to the correct format to be inserted into the database.
    $arguments = implode(", ",array_keys($data[0]));
    $arguments = "(".$arguments.")";
    $all_values = [];
    // Iterate all the values and add single quotes to apply with the format.
    foreach($data as $element){
        $arr = array_values($element);
        foreach ($arr as &$value) {
            $value = '"'.$value.'"';
        }
        $values = implode(", ",$arr);
        $values = "(".$values.")";
        // Push each formatted value to the overall values array.
        array_push($all_values,$values);
    }
    // Perform a single query to add all the rows to the table at once.
    $db->insert_into($table_name . " " . $arguments)->values(implode(", ",$all_values))->execute();
}

/**
 * Get the total summary of sales on orders with paid or partially paid financial status from the database.
 */
function getNetSales(){
    global $db;
    // Conduct the required query.
    $response = $db->select('total_price')->from('orders')->
    where("financial_status = '".FinancialStatus::paid."'")->
    or("financial_status = '".FinancialStatus::partially_paid."'")
    ->execute()->fetchAll(PDO::FETCH_ASSOC);
    // Calculate the sum.
    return getSumOfArrays($response, 'total_price');
}

/**
 * Get the total production costs on orders with paid or partially paid financial status
 * and a fulfilled fulfillment status from the database.
 */
function getProductionCosts(){
    global $db;
    // Conduct the required query.
    $response = $db->select('total_production_cost')->from('orders')->
    where("financial_status = '".FinancialStatus::paid."'")->
    or("financial_status = '".FinancialStatus::partially_paid."'")->
    and("fulfillment_status = '".FulfillmentStatus::fulfilled."'")->
    execute()->fetchAll(PDO::FETCH_ASSOC);
    // Calculate the sum.
    return getSumOfArrays($response, 'total_production_cost');
}

/**
 * Get the sum of all values in the provided arrays with a given key.
 * @param $arrays = the arrays upon we conduct the calculation.
 * @param $key = the key in each array to sum its value.
 * @return float the sum of all values or null if no arrays given. 
 */
function getSumOfArrays($arrays, $key){
    if($arrays){
        $sum = 0;
        foreach($arrays as $element){
            // Cast the value to float to use arithmetic operators. 
            $sum += floatval($element[$key]);
        }
        return $sum;
    }
    return NULL;
}
?>