<html>
    <head>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <title>Open Interest</title>
    </head>
    <body><?php
        function roundValue($value){
            $vali_value = substr($value, -2);
            if($vali_value <= 25){
                return $value - $vali_value;
            }else if($vali_value >= 75){
                return $value + (100 - $vali_value);
            }else{
                return $value - ($vali_value - 50);
            }
        }
    
        $nifty_call_option_arr = [];
        $nifty_put_option_arr = [];
        $oi_pcr = 0;
        $coi_pcr = 0;
        $total_pe_oi = 0;
        $total_pe_coi = 0;
        $total_ce_oi = 0;
        $total_ce_coi = 0;
        $final_oi = 0;
        function getOI($howManyStrikeFromSpotPrice){
            global $nifty_call_option_arr, $nifty_put_option_arr, $oi_pcr, $coi_pcr, $total_pe_coi, $total_pe_oi, $total_ce_coi, $total_ce_oi, $final_oi;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.nseindia.com/api/option-chain-indices?symbol=NIFTY');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = json_decode(curl_exec($ch), true);
            $closing_price = roundValue(floor($result['records']['underlyingValue']));
            $all_arr = array_keys(array_column($result['records']['data'], 'expiryDate'), $result['records']['expiryDates'][0]);
            // $closing_price_posi = array_search($closing_price, $all_arr);
            // $count = count($all_arr);
            $closing_key = '';
            foreach($all_arr as $key=>$aa){
                if($result['records']['data'][$aa]['CE']['strikePrice'] == $closing_price){
                    $closing_key = $key;
                }
            }
            foreach($all_arr as $key=>$val){
                if($key < ($closing_key - $howManyStrikeFromSpotPrice) || $key > ($closing_key + $howManyStrikeFromSpotPrice)){
                    unset($all_arr[$key]);
                }
            }
            $a = 0;
            foreach($all_arr as $val){
                $total_pe_oi += $result['records']['data'][$val]['PE']['openInterest'];
                $total_pe_coi += $result['records']['data'][$val]['PE']['changeinOpenInterest'];
                $total_ce_oi += $result['records']['data'][$val]['CE']['openInterest'];
                $total_ce_coi += $result['records']['data'][$val]['CE']['changeinOpenInterest'];
    
                $nifty_call_option_arr[$a]['strike'] = $result['records']['data'][$val]['strikePrice'];
                $nifty_call_option_arr[$a]['last'] = $result['records']['data'][$val]['CE']['lastPrice'];
                $nifty_call_option_arr[$a]['openint'] = $result['records']['data'][$val]['CE']['openInterest'];
                $nifty_call_option_arr[$a]['changeinoi'] = $result['records']['data'][$val]['CE']['changeinOpenInterest'];
    
                $nifty_put_option_arr[$a]['strike'] = $result['records']['data'][$val]['strikePrice'];
                $nifty_put_option_arr[$a]['last'] = $result['records']['data'][$val]['PE']['lastPrice'];
                $nifty_put_option_arr[$a]['openint'] = $result['records']['data'][$val]['PE']['openInterest'];
                $nifty_put_option_arr[$a]['changeinoi'] = $result['records']['data'][$val]['PE']['changeinOpenInterest'];
                $a++;
            }
            $oi_pcr = $total_pe_oi / $total_ce_oi;
            $coi_pcr = $total_pe_coi / $total_ce_coi;

            $final_oi = ($total_pe_oi + $total_pe_coi) / ($total_ce_oi + $total_ce_coi);
        }
        
        $howManyStrikeFromSpotPrice = 8;
        getOI($howManyStrikeFromSpotPrice); ?>
        <div class="container">
            <h1 class="text-center">NIFTY 50</h1>
            <div class="row g-3">
                <div class="col-4 d-flex justify-content-center">
                    <h4 style="color: #F05969;">Open Interest (PCR): <?php echo round($oi_pcr, 2); ?></h4>
                </div>
                <div class="col-4 d-flex justify-content-center">
                    <h4 style="color: #F05969;">Change in Open Interest (PCR): <?php echo round($coi_pcr, 2); ?></h4>
                </div>
                <div class="col-4 d-flex justify-content-center">
                    <h4 style="color: #F05969;">Change in OI + OI (PCR): <?php echo round($final_oi, 2); ?></h4>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered text-center">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" colspan="4">NIFTY CALL OPTION</th>
                            </tr>
                            <tr>
                                <th scope="col">STRIKE</th>
                                <th scope="col">LAST</th>
                                <th scope="col">OPEN INT</th>
                                <th scope="col">CHANGE IN OI</th>
                            </tr>
                        </thead>
                        <tbody><?php
                        foreach(array_reverse($nifty_call_option_arr) as $ncoa){ ?>
                            <tr>
                                <th scope="row"><?php echo $ncoa['strike']; ?></th>
                                <td><?php echo $ncoa['last']; ?></td>
                                <td><?php echo $ncoa['openint']; ?></td>
                                <td><?php echo $ncoa['changeinoi']; ?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th scope="row" colspan="2">Total</th>
                                <td><?php echo $total_ce_oi; ?></td>
                                <td><?php echo $total_ce_coi; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered text-center">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" colspan="4">NIFTY PUT OPTION</th>
                            </tr>
                            <tr>
                                <th scope="col">STRIKE</th>
                                <th scope="col">LAST</th>
                                <th scope="col">OPEN INT</th>
                                <th scope="col">CHANGE IN OI</th>
                            </tr>
                        </thead>
                        <tbody><?php
                        foreach(array_reverse($nifty_put_option_arr) as $npoa){ ?>
                            <tr>
                                <th scope="row"><?php echo $npoa['strike']; ?></th>
                                <td><?php echo $npoa['last']; ?></td>
                                <td><?php echo $npoa['openint']; ?></td>
                                <td><?php echo $npoa['changeinoi']; ?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th scope="row" colspan="2">Total</th>
                                <td><?php echo $total_pe_oi; ?></td>
                                <td><?php echo $total_pe_coi; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <script>
            var myInterval = null;
            function interval(){
                const date = new Date();
                let minutes = date.getMinutes();
                if(String(minutes).slice(-1) == 5 || String(minutes).slice(-1) == 0){
                    clearInterval(myInterval);
                    location.reload();
                }
            }
            const date = new Date();
            let minutes = date.getMinutes();
            if(String(minutes).slice(-1) == 5 || String(minutes).slice(-1) == 0){
                clearInterval(myInterval);
                setTimeout(function(){
                    location.reload();
                }, 300000);
            }else{
                clearInterval(myInterval);
                myInterval = setInterval(interval, 1000);
            }
        </script>
    </body>
</html>