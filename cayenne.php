<?php
$data = $_REQUEST['data'];
?>
<html>
<head>
 


</head>
<body>

 

<script>
/**
 * Cayenne Low-power Payload Library
 * 
 * @author Pasakorn Tiwatthanont
 * @email ptiwatthanont@gmail.com
 * 
 * @link https://htmlcheatsheet.com/js/ 
 */
// console.log('Node.js version: ' + process.version)
 
/**
 * Byte stream to fixed-point decimal word.
 * 
 * @param stream: array of bytes.
 * @return: word of the bytes in big-endian format.
 */
 

 
 
function arrayToDecimal(stream, is_signed=false, decimal_point=0) {
    var value = 0;
    for (var i = 0; i < stream.length; i++) {
        if (stream[i] > 0xFF) 
            throw 'Byte value overflow!';
        value = (value<<8) | stream[i];
    }
    
    if (is_signed) {
        edge  = 1 << (stream.length  )*8;  // 0x1000..
        max   = (edge-1) >> 1;             // 0x0FFF.. >> 1
        value = (value > max) ? value - edge : value;
    }    
    
    if (decimal_point) {
        value /= Math.pow(10, decimal_point);
    }
    
    return value;
}

/**
 * Cayenne Low-power Payload Decoder
 * 
 * @param payload: array of bytes.
 * @return: JSON
 */
function decode(payload) {
    var arrNew = [];
    for(var i =0; i<payload.length; i=i+2) {
      arrNew.push(parseInt("0x" + payload[i] + payload[i+1]));
    
    }
    payload = arrNew;
    console.log(payload);
    if (payload == null)
        payload = [ 
            0x01, 0x67, 0x00, 0xE1,  // "temperature_1"        : 22.5   (fixed point)
            0x02, 0x73, 0x29, 0xEC,  // "barometric_pressure_2": 1073.2 (fixed point)
            0x03, 0x88,              // "gps_3":
            0x02, 0xDD, 0xFC,        //          { "latitude" : 18.79,
            0x0F, 0x1A, 0x68,        //            "longitude": 98.98, 
            0x00, 0x79, 0x18,        //            "altitude" : 310 }
            ]  
    
    /**
     * @reference https://github.com/myDevicesIoT/cayenne-docs/blob/master/docs/LORA.md
     * 
     * Type                 IPSO    LPP     Hex     Data Size   Data Resolution per bit
     *  Digital Input       3200    0       0       1           1
     *  Digital Output      3201    1       1       1           1
     *  Analog Input        3202    2       2       2           0.01 Signed
     *  Analog Output       3203    3       3       2           0.01 Signed
     *  Illuminance Sensor  3301    101     65      2           1 Lux Unsigned MSB
     *  Presence Sensor     3302    102     66      1           1
     *  Temperature Sensor  3303    103     67      2           0.1 °C Signed MSB
     *  Humidity Sensor     3304    104     68      1           0.5 % Unsigned
     *  Accelerometer       3313    113     71      6           0.001 G Signed MSB per axis
     *  Barometer           3315    115     73      2           0.1 hPa Unsigned MSB
     *  Gyrometer           3334    134     86      6           0.01 °/s Signed MSB per axis
     *  GPS Location        3336    136     88      9           Latitude  : 0.0001 ° Signed MSB
     *                                                          Longitude : 0.0001 ° Signed MSB
     *                                                          Altitude  : 0.01 meter Signed MSB
     */    
    var sensor_types = { 
            0  : {'size': 1, 'name': 'Digital Input'      , 'signed': false, 'decimal_point': 0,},
            1  : {'size': 1, 'name': 'Digital Output'     , 'signed': false, 'decimal_point': 0,},
            2  : {'size': 2, 'name': 'Analog Input'       , 'signed': true , 'decimal_point': 2,},
            3  : {'size': 2, 'name': 'Analog Output'      , 'signed': true , 'decimal_point': 2,},
            101: {'size': 2, 'name': 'Illuminance Sensor' , 'signed': false, 'decimal_point': 0,},
            102: {'size': 1, 'name': 'Presence Sensor'    , 'signed': false, 'decimal_point': 0,},
            103: {'size': 2, 'name': 'Temperature Sensor' , 'signed': true , 'decimal_point': 1,},
            104: {'size': 1, 'name': 'Humidity Sensor'    , 'signed': false, 'decimal_point': 1,},
            113: {'size': 6, 'name': 'Accelerometer'      , 'signed': true , 'decimal_point': 3,},
            115: {'size': 2, 'name': 'Barometer'          , 'signed': false, 'decimal_point': 1,},
            134: {'size': 6, 'name': 'Gyrometer'          , 'signed': true , 'decimal_point': 2,},
            136: {'size': 9, 'name': 'GPS Location'       , 'signed': true, 'decimal_point': [4,4,2], },};
    
    var sensors = {};
    var i = 0;
	while (i < payload.length) {
	  // console.log(i);
	  // console.log(typeof payload[i])
	  // console.log(payload[i].toString())
	  s_no  = payload[i++];

		s_type = payload[i++];

    console.log(s_no, s_type);
		if (typeof sensor_types[s_type] != 'undefined')
      //alert(s_type);
		    //throw format('Sensor type error!: {}', s_type);	
	    s_size = sensor_types[s_type].size;
	    s_name = sensor_types[s_type].name;
	    
	    switch (s_type) {
    	    case 0  :  // Digital Input
            case 1  :  // Digital Output
            case 2  :  // Analog Input
            case 3  :  // Analog Output
            case 101:  // Illuminance Sensor
            case 102:  // Presence Sensor
            case 103:  // Temperature Sensor
            case 104:  // Humidity Sensor
            case 113:  // Accelerometer
            case 115:  // Barometer
            case 134:  // Gyrometer
                s_value = arrayToDecimal(payload.slice(i, i+s_size), 
                        is_signed     = sensor_types[s_type].signed, 
                        decimal_point = sensor_types[s_type].decimal_point);                
                //console.log(format('no:{} size:{} type:{} value:{}', s_no, s_size, s_name, s_value));
                break;
            case 136:  // GPS Location
                //console.log(payload.slice(i+0, i+3), payload.slice(i+3, i+6), payload.slice(i+3, i+9),  arrayToDecimal(payload.slice(i+3, i+6), true, decimal_point=sensor_types[s_type].decimal_point[0]))
                s_value = {
                        'latitude':  arrayToDecimal(payload.slice(i+0, i+3), is_signed=sensor_types[s_type].signed, decimal_point=sensor_types[s_type].decimal_point[0]), 
                        'longitude': arrayToDecimal(payload.slice(i+3, i+6), is_signed=sensor_types[s_type].signed, decimal_point=sensor_types[s_type].decimal_point[1]), 
                        'altitude':  arrayToDecimal(payload.slice(i+6, i+9), is_signed=sensor_types[s_type].signed, decimal_point=sensor_types[s_type].decimal_point[2]),};
                //console.log(format('no:{} size:{} type:{} lat:{} lon:{} alt:{}', s_no, s_size, s_name, s_value.lat, s_value.lon, s_value.alt));
                break;
        }
	    
	    sensors[s_no] = {'type': s_type, 'type_name': s_name, 'value': s_value };
	    i += s_size;
	}
	
	return sensors;
}

function syntaxHighlight(json) {
    if (typeof json != 'string') {
         json = JSON.stringify(json, undefined, 2);
    }
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'key';
            } else {
                cls = 'string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'boolean';
        } else if (/null/.test(match)) {
            cls = 'null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}
var data = "<?php echo $data; ?>";
 
var decoded = decode(data);
var str = syntaxHighlight(decoded); // spacing level = 2
 

</script>
<pre>
<script>
document.write(str);
</script>

</pre>
<form method=get>
<table>
<tr>
<td>cayenne data packet</td>
<td><input name='data' size='80' value='<?php echo $data;?>'/></td>
</tr>
<tr>
 <td colspan=2>
 <input type='submit'  value='decode cayenne data' />
 
 </td>
</tr>
</table>
</form>
</body>
</html>