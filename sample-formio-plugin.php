 <?php
/*
* Plugin Name: Sample Display Tables
* Description: Adds a display of Form.io submissions to the page by a shortcode
* Version: 1.0
* Author: JB Design
* Author URI: https://www.joshuaberkowitz.us
*/


class Formio {
    public $project = '';
    public $token = '';
    public $options = array(
      'resource' => 'user',
      'login' => 'user/login',
      'register' => 'user/register',
      'id_field' => 'email',
      'password_field' => 'password',
      'default_password' => ''
    );
    public function __construct($project, $options = array()) {
      $this->project = $project;
      foreach ($this->options as $key => $default) {
        if (isset($options[$key])) {
          $this->options[$key] = $options[$key];
        }
      }
    }
    private function getHeaders($header) {
      $headers = array();
      foreach (explode("\r\n", $header) as $i => $line) {
        if ($i === 0) {
          $headers['http_code'] = $line;
        }
        else {
          list ($key, $value) = explode(': ', $line);
          $headers[$key] = $value;
        }
      }
      return $headers;
    }
    private function request($curl) {
      $headers = array(
        "cache-control: no-cache",
        "content-type: application/json"
      );
      if ($this->token) {
        array_push($headers, 'x-jwt-token: ' . $this->token);
      }
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_ENCODING, '');
      curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
      curl_setopt($curl, CURLOPT_TIMEOUT, 30);
      curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HEADER, 1);
      $response = curl_exec($curl);
      list($header, $body) = explode("\r\n\r\n", $response, 2);
      $err = curl_error($curl);
      curl_close($curl);
      if ($err) {
        echo "cURL Error #:" . $err;
        $retVal = array('headers' => array(), 'body' => array(), 'error' => $err);
      } else {
        $retVal = array(
          'headers' => $this->getHeaders($header),
          'body' => json_decode($body, true)
        );
      }
      return $retVal;
    }
    public function get($path) {
      $url = $this->project . '/' . $path;
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "GET",
      ));
      return $this->request($curl);
    }
    public function del($path) {
      $url = $this->project . '/' . $path;
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "DELETE"
      ));
      return $this->request($curl);
    }
    public function put($path, $body) {
      $url = $this->project . '/' . $path;
      $curl = curl_init();
      $data = json_encode($body);
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => $data,
      ));
      return $this->request($curl);
    }
    public function post($path, $body) {
      $url = $this->project . '/' . $path;
      $curl = curl_init();
      $data = json_encode($body);
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
      ));
      return $this->request($curl);
    }
    /**
     * Checks to see if a submission exists.
     */
    public function exists($id) {
      $path = $this->options['resource'];
      $path .= '/exists?data.' . $this->options['id_field'] . '=' . $id;
      $response = $this->get($path);
      return !!$response['body']['_id'];
    }
    /**
     * Log in an existing user.
     */
    public function login($id, $password = '') {
      $body = array('data' => array());
      $body['data'][$this->options['id_field']] = $id;
      if (!$password) {
        $password = $this->options['default_password'];
      }
      $body['data'][$this->options['password_field']] = $password;
      $response = $this->post($this->options['login'], $body);
      $this->token = $response['headers']['x-jwt-token'];
      return $response['body'];
    }
    /**
     * Register a new user.
     */
    public function register($id, $password = '', $data = array()) {
      $body = array('data' => array());
      $body['data'][$this->options['id_field']] = $id;
      if (!$password) {
        $password = $this->options['default_password'];
      }
      $body['data'][$this->options['password_field']] = $password;
      foreach ($data as $key => $value) {
        if (!isset($body['data'][$key])) {
          $body['data'][$key] = $value;
        }
      }
      $response = $this->post($this->options['register'], $body);
      $this->token = $response['headers']['x-jwt-token'];
      return $response['body'];
    }
    /**
     * Performs a single-sign-on within Form.io and returns their token.
     *
     *   1.) Checks to see if the user exists.
     *   2.) If so, then logs them in and returns their token.
     *   3.) If not, then it creates their account with default password and returs their token.
     */
    public function sso($id, $data = array()) {
      if ($this->exists($id)) {
        return $this->login($id);
      }
      else {
        return $this->register($id, '', $data);
      }
    }
    /**
     * Returns the token after an sso attempt.
     */
    public function ssoToken($id) {
      $this->sso($id);
      return $this->token;
    }
  }
  

function display_formio_sample_inquiry_entries(){

    /*
    *Create a new instance from the project URL and the resource
    */
    $formio = new Formio('https://hqmmszjedyurvoc.form.io', array(
        'resource' => 'admin', // Formio resource against which to authenticate
        'login' => 'admin/login' // Formio login endpoint
    ));

    /*
    * Call login() to retreive a jwt-token for authentication
    * Replace {{username}} and {{password}}
    */

    $tables = $formio->login('{{username@email.com}}', '{{password}}');
    
    /* 
    * jwt token is now stored in the class and can be used for get() and post() calls
    * the get() method takes only the endpoint (or filtered endpoint) and returns a reponse object containing form details and array of submission objects
    */

    $productInquiryTables = $formio->get('sampleinquiryform/submission');

    //The response body contains a "body" property which holds all the response information
    $formEntries = $productInquiryTables["body"];

    /* 
     * VERY VERY SIMPLE Table implementation for illustration purposes only
     * $tableTop contains the table styles and header/heading, this is static
     */
      
    $tableTop = '<style type="text/css">
    .tg  {border-collapse:collapse;border-spacing:0;}
    .tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    .tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    .tg .tg-0pky{border-color:inherit;text-align:left;vertical-align:top}
    .tg .tg-0lax{text-align:left;vertical-align:top}
    </style>
    <h3>Product Inquiry Submissions</h3>
    <table class="tg">
      <tr>        
        <th class="tg-0pky">Date</th>
        <th class="tg-0lax">Name</th>
        <th class="tg-0lax">Email</th>
        <th class="tg-0lax">Phone</th>
      </tr>';

      echo $tableTop;

      /*
      * Simply loop through each of the objects to display the properties you need in bracket notation
      * Note the resopnse body on the formio docs is the $productInquiryTables["body"] variable
      */

    foreach($formEntries as $entry){

        //Making the date a date type to format in the table
        $date = date_create($entry["created"]);

        echo '<tr>';
        echo '<td class="tg-0pky">' . date_format($date, "m/d/y") . '</td>'; //Output the date at 01/01/19
        echo '<td class="tg-0lax">' . $entry["data"]["fullName"] . '</td>';  // List the full name field entries
        echo '<td class="tg-0lax">' . $entry["data"]["email"] . '</td>';     // List the email address field enteries
        echo '<td class="tg-0lax">' . $entry["data"]["phoneNumber"] . '</td>'; // List the phone number field enteries
        echo '</tr>';


    }


    echo '</table>'; //Close our table
    


}
//Add  a custom shortcode code that calls the function to display the table above
add_shortcode('formio-inquiry', 'display_formio_sample_inquiry_entries');


?>