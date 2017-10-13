<?php
namespace Pepijnolivier\Kraken;

class Client implements ClientContract
{

    protected $key;     // API key
    protected $secret;  // API secret
    protected $url;     // API base URL
    protected $version; // API version
    protected $curl;    // curl handle
    /**
     * Constructor for KrakenAPI
     *
     * @param string $key API key
     * @param string $secret API secret
     * @param string $url base URL for Kraken API
     * @param string $version API version
     * @param bool $sslverify enable/disable SSL peer verification.  disable if using beta.api.kraken.com
     */
    function __construct($key, $secret, $url='https://api.kraken.com', $version='0', $sslverify=true)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->url = $url;
        $this->version = $version;
        $this->curl = curl_init();

        curl_setopt_array($this->curl, array(
                CURLOPT_SSL_VERIFYPEER => $sslverify,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'Kraken PHP API Agent',
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true)
        );
    }


    /**
     * Note: This is to aid in approximating the skew time between the server and client.
     * @return mixed Servers' time
     */
    public function getServerTime()
    {
        return $this->public('Time');
    }

    /**
     * Get asset info
     *
     * @return array of asset names and their info
     */
    public function getAssetInfo()
    {
        return $this->public('Assets');
    }

    /**
     * Get tradable asset pairs
     *
     * @return array of pair names and their info
     */
    public function getAssetPairs(array $pairs=null, string $info='info')
    {
        $csv = empty($pairs) ? null : implode(',', $pairs);
        return $this->public('AssetPairs', array_filter([
            'pair' => $csv,
            'info' => $info,
        ]));
    }

    /**
     * Get tickers information
     *
     * @param array $pairs
     * @return array of pair names and their ticker info
     */
    public function getTickers(array $pairs)
    {
        $csv = implode(',', $pairs);
        return $this->public('Ticker', [
            'pair' => $csv
        ]);

    }

    /**
     * Get OHLC Data
     *
     * @param string $currencypair
     * @param int $timeFrameIntervalMinutes
     * @param string $sinceId return committed OHLC data since given id (optional.  exclusive)
     * @return mixed
     */
    public function getTicker(string $currencypair, int $timeFrameIntervalMinutes=1, string $sinceId=null)
    {
        return $this->public('OHLC', array_filter([
            'pair' => $currencypair,
            'interval' => $timeFrameIntervalMinutes,
            'since' => $sinceId,
        ]));
    }

    /**
     * Get order book
     *
     * @param string $pair asset pair to get market depth for
     * @param int|null $count maximum number of asks/bids (optional)
     * @return mixed
     */
    public function getOrderBook(string $pair, int $count = null)
    {
        return $this->public('Depth', array_filter([
            'pair' => $pair,
            'count' => $count,
        ]));
    }

    /**
     * Get recent trades
     *
     * @param string $pair asset pair to get trade data for
     * @param string|null $sinceId return trade data since given id (optional.  exclusive)
     * @return mixed
     */
    public function getRecentTrades(string $pair, string $sinceId = null)
    {
        return $this->public('Trades', array_filter([
            'pair' => $pair,
            'since' => $sinceId,
        ]));
    }

    /**
     * Get recent spread data
     * Note: "since" is inclusive so any returned data with the same time as the previous set
     * should overwrite all of the previous set's entries at that time
     *
     * @param string $pair asset pair to get spread data for
     * @param string|null $sinceId return spread data since given id (optional.  inclusive)
     * @return mixed
     */
    public function getRecentSpreads(string $pair, string $sinceId = null)
    {
        return $this->public('Spread', array_filter([
            'pair' => $pair,
            'since' => $sinceId,
        ]));
    }

    /**
     * Get balances
     *
     * @return array of asset names and balance amount
     */
    public function getBalances()
    {
        return $this->private('Balance');
    }

    /**
     * Get trade balance
     * Note: Rates used for the floating valuation is the midpoint of the best bid and ask prices
     *
     * eb = equivalent balance (combined balance of all currencies)
     * tb = trade balance (combined balance of all equity currencies)
     * m = margin amount of open positions
     * n = unrealized net profit/loss of open positions
     * c = cost basis of open positions
     * v = current floating valuation of open positions
     * e = equity = trade balance + unrealized net profit/loss
     * mf = free margin = equity - initial margin (maximum margin available to open new positions)
     * ml = margin level = (equity / initial margin) * 100
     *
     * @param string $currency
     * @param string $baseCurrency
     * @param string|null $assetClass
     * @return array of trade balance info
     */
    public function getTradeBalance(string $currency, string $baseCurrency, string $assetClass = null)
    {
        return $this->private('TradeBalance', array_filter([
            'currency' => $currency,
            'asset' => $baseCurrency,
            'aclass' => $assetClass,
        ]));
    }

    /**
     * Get open orders
     * See https://www.kraken.com/help/api#get-open-orders
     *
     * Note: Unless otherwise stated, costs, fees, prices, and volumes are in the asset pair's scale, not the currency's scale.
     * For example, if the asset pair uses a lot size that has a scale of 8,
     * the volume will use a scale of 8, even if the currency it represents only has a scale of 2.
     * Similarly, if the asset pair's pricing scale is 5,
     * the scale will remain as 5, even if the underlying currency has a scale of 8.
     *
     * @param bool $includeTrades whether or not to include trades in output (optional.  default = false)
     * @param string|null $userRef restrict results to given user reference id (optional)
     * @return array of order info in open array with txid as the key
     */
    public function getOpenOrders(bool $includeTrades = false, string $userRef = null)
    {
        return $this->private('OpenOrders', array_filter([
           'trades' => $includeTrades,
           'userref' => $userRef,
        ]));
    }

    /**
     * Get closed orders
     * Note: Times given by order tx ids are more accurate than unix timestamps.
     * If an order tx id is given for the time, the order's open time is used
     *
     * @param int|null $startUnixTimestamp starting unix timestamp or order tx id of results (optional.  exclusive)
     * @param int|null $endUnixTimestamp ending unix timestamp or order tx id of results (optional.  inclusive)
     * @param int|null $offset result offset
     * @param string $closeTime which time to use (open, close, both)
     * @param bool $includeTrades whether or not to include trades in output (optional.  default = false)
     * @param string|null $userRef restrict results to given user reference id (optional)
     * @return array of order info
     */
    public function getClosedOrders(
        int $startUnixTimestamp = null,
        int $endUnixTimestamp = null,
        int $offset = null,
        string $closeTime = 'both',
        bool $includeTrades = false,
        string $userRef = null
    ) {
        return $this->private('ClosedOrders', array_filter([
            'trades' => $includeTrades,
            'userref' => $userRef,
            'start' => $startUnixTimestamp,
            'end' => $endUnixTimestamp,
            'ofs' => $offset,
            'closetime' => $closeTime,
        ]));
    }

    /**
     * Get order info
     *
     * @param array $transactionIds (max 20)
     * @param bool $includeTrades whether or not to include trades in output (optional.  default = false)
     * @param string|null $userRef restrict results to given user reference id (optional)
     * @return array containing orders info
     */
    public function getOrdersInfo(array $transactionIds, bool $includeTrades = false, string $userRef = null)
    {
        $csv = implode(',', $transactionIds);
        return $this->private('QueryOrders', array_filter([
            'trades' => $includeTrades,
            'userref' => $userRef,
            'txid' => $csv,
        ]));
    }

    /**
     * Get trades history
     * See https://www.kraken.com/help/api#get-trades-history
     *
     * @param string $type type of trade:
     *
     *                      all = all types (default)
     *                      any position = any position (open or closed)
     *                      closed position = positions that have been closed
     *                      closing position = any trade closing all or part of a position
     *                      no position = non-positional trades
     * @param int $startUnixTimestamp starting unix timestamp or trade tx id of results (optional.  exclusive)
     * @param int $endUnixTimestamp ending unix timestamp or trade tx id of results (optional.  inclusive)
     * @param int|null $offset result offset
     * @param bool $includeTrades whether or not to include trades related to position in output (optional.  default = false)
     * @return array of trade info
     */
    public function getTradesHistory(
        string $type = 'all',
        int $startUnixTimestamp = null,
        int $endUnixTimestamp = null,
        int $offset = null,
        bool $includeTrades = false
    ) {
        return $this->private('TradesHistory', array_filter([
            'type' => $type,
            'start' => $startUnixTimestamp,
            'end' => $endUnixTimestamp,
            'ofs' => $offset,
            'trades' => $includeTrades,
        ]));
    }

    /**
     * Get trades info
     *
     * @param array $transactionIds list of transaction ids to query info about (20 maximum)
     * @param bool $includeRelatedTrades whether or not to include trades related to position in output (optional.  default = false)
     * @return array of trades info
     */
    public function getTradesInfo(array $transactionIds, bool $includeRelatedTrades = false)
    {
        $csv = implode(',', $transactionIds);
        return $this->private('QueryTrades', array_filter([
            'txid' => $csv,
            'trades' => $includeRelatedTrades,
        ]));
    }

    /**
     * Get open positions
     * See https://www.kraken.com/help/api#get-open-positions
     *
     * @param array $transactionIds list of transaction ids to restrict output to
     * @param bool $calculateProfitLoss whether or not to include profit/loss calculations (optional.  default = false)
     * @return array of open position info
     */
    public function getOpenPositions(array $transactionIds, bool $calculateProfitLoss = false)
    {
        $csv = implode(',', $transactionIds);
        return $this->private('OpenPositions', array_filter([
            'txid' => $csv,
            'docalcs' => $calculateProfitLoss,
        ]));
    }

    /**
     * Get ledgers info
     * See https://www.kraken.com/help/api#get-ledgers-info
     *
     * @param string $currency
     * @param int|null $startUnixTimestamp starting unix timestamp or ledger id of results (optional.  exclusive)
     * @param int|null $endUnixTimestamp ending unix timestamp or ledger id of results (optional.  inclusive)
     * @param int|null $offset result offset
     * @param string $type type of ledger to retrieve (all, deposit, withdrawal, trade, margin)
     * @param string|null $assetClass asset class (optional):
     * @return array of ledgers info
     */
    public function getLedgers(
        string $currency,
        int $startUnixTimestamp = null,
        int $endUnixTimestamp = null,
        int $offset = null,
        string $type = 'all',
        string $assetClass = null
    ) {
        return $this->private('Ledgers', array_filter([
            'currency' => $currency,
            'start' => $startUnixTimestamp,
            'end' => $endUnixTimestamp,
            'ofs' => $offset,
            'type' => $type,
            'aclass' => $assetClass,
        ]));
    }

    /**
     * @param array $ledgerIds list of ledger ids to query info about (20 maximum)
     * @return array of ledgers info
     */
    public function getLedgersInfo(array $ledgerIds)
    {
        $csv = implode(',', $ledgerIds);
        return $this->private('QueryLedgers', [
            'id' => $csv,
        ]);
    }

    /**
     * Get trade volume
     * See https://www.kraken.com/help/api#get-trade-volume
     *
     * @param array $currencypairs list of asset pairs to get fee info on (optional)
     * @param bool $includeFeeInfo whether or not to include fee info in results (optional)
     * @return mixed
     */
    public function getTradeVolume(array $currencypairs = null, bool $includeFeeInfo = false)
    {
        $csv = implode(',', $currencypairs);
        return $this->private('TradeVolume', array_filter([
            'pair' => $csv,
            'fee-info' => $includeFeeInfo,
        ]));
    }

    /**
     * Add an order (buy or sell)
     * See https://www.kraken.com/help/api#add-standard-order
     *
     * @param string $currencypair
     * @param string $buyOrSell
     * @param string $orderType
     * @param string $price
     * @param string $price2
     * @param $volume
     * @param string $leverage
     * @param array $oflags
     * @param string|null $startTm
     * @param string|null $expireTm
     * @param string|null $userRef
     * @param bool $validateOnly
     * @return mixed
     */
    public function addOrder(
        string $currencypair,
        string $buyOrSell,
        string $orderType,
        string $volume,
        string $price,
        string $price2 = null,
        $leverage = 'none',
        array $oflags = [],
        string $startTm = null,
        string $expireTm = null,
        string $userRef = null,
        bool $validateOnly = false
    ) {
        $data = array_filter([
            'pair' => $currencypair,
            'type' => $buyOrSell,
            'ordertype' => $orderType,
            'price' => $price,
            'price2' => $price2,
            'volume' => $volume,
            'leverage' => $leverage,
            'oflags' => implode(',', $oflags),
            'starttm' => $startTm,
            'expiretm' => $expireTm,
            'userref' => $userRef,
            'validate' => $validateOnly,
        ]);
        return $this->private('AddOrder', $data);
    }

    /**
     * @param string $transactionId
     * @return mixed
     */
    public function cancelOrder(string $transactionId)
    {
        return $this->private('CancelOrder', [
            'txid' => $transactionId,
        ]);
    }

    /**
     * Limit BUY
     *
     * @param string $currencypair
     * @param string $quantity
     * @param string $rate
     * @return mixed
     */
    public function buy(string $currencypair, string $quantity, string $rate)
    {
        return $this->addOrder($currencypair, 'buy', 'limit', $quantity, $rate);
    }

    /**
     * Limit SELL
     *
     * @param string $currencypair
     * @param string $quantity
     * @param string $rate
     * @return mixed
     */
    public function sell(string $currencypair, string $quantity, string $rate)
    {
        return $this->addOrder($currencypair, 'sell', 'limit', $quantity, $rate);
    }


    /**
     * Get deposit methods
     *
     * Returns data:
     *     method = name of deposit method
     *     limit = maximum net amount that can be deposited right now, or false if no limit
     *     fee = amount of fees that will be paid
     *     address-setup-fee = whether or not method has an address setup fee (optional)
     *
     * @param string $currency
     * @param string $assetClass
     * @return array of deposit methods
     */
    public function getDepositMethods(string $currency, string $assetClass=null)
    {
        return $this->private('DepositMethods', array_filter([
            'asset' => $currency,
            'aclass' => $assetClass,
        ]));
    }

    /**
     * @param string $currency
     * @param string $method
     * @param bool $new
     * @param string|null $assetClass
     * @return array of deposit addresses
     */
    public function getDepositAddresses(string $currency, string $method, bool $new=false, string $assetClass=null) {
        return $this->private('DepositAddresses', array_filter([
            'asset' => $currency,
            'aclass' => $assetClass,
            'method' => $method,
            'new' => $new
        ]));
    }

    /**
     * See https://www.kraken.com/help/api#deposit-status
     *
     * @param string $currency
     * @param string|null $method
     * @param string|null $assetClass
     * @return array of deposit status information
     */
    public function getDepositStatus(string $currency, string $method=null, string $assetClass = null)
    {
        return $this->private('DepositStatus', array_filter([
            'asset' => $currency,
            'method' => $method,
            'aclass' => $assetClass,
        ]));
    }

    /**
     * @param string $currency
     * @param string $key
     * @param string $amount
     * @param string|null $assetClass
     * @return array of withdrawal info
     */
    public function getWithdrawInfo(string $currency, string $key, string $amount, string $assetClass = null)
    {
        $data = array_filter([
            'asset' => $currency,
            'key' => $key,
            'amount' => $amount,
            'aclass' => $assetClass,
        ]);
        return $this->private('WithdrawInfo', $data);
    }

    /**
     * @param string $currency
     * @param string $key
     * @param string $amount
     * @param string|null $assetClass
     * @return array of withdrawal transactio
     */
    public function withdraw(string $currency, string $key, string $amount, string $assetClass = null)
    {
        return $this->private('Withdraw', array_filter([
            'asset' => $currency,
            'key' => $key,
            'amount' => $amount,
            'aclass' => $assetClass,
        ]));
    }

    /**
     * See https://www.kraken.com/help/api#withdraw-status
     *
     * @param string $currency
     * @param string|null $method
     * @param string|null $assetClass
     * @return array of withdrawal status information
     */
    public function getWithdrawalStatus(string $currency, string $method=null, string $assetClass = null)
    {
        return $this->private('WithdrawStatus', array_filter([
            'asset' => $currency,
            'method' => $method,
            'aclass' => $assetClass,
        ]));
    }

    /**
     * Cancellation cannot be guaranteed. This will put in a cancellation request.
     * Depending upon how far along the withdrawal process is, it may not be possible to cancel the withdrawal.
     * @param string $currency
     * @param string $referenceId
     * @param string|null $assetClass
     *
     * @return mixed
     */
    public function cancelWithdrawal(string $currency, string $referenceId, string $assetClass = null)
    {
        return $this->private('WithdrawCancel', array_filter([
            'asset' => $currency,
            'refid' => $referenceId,
            'aclass' => $assetClass,
        ]));
    }

    /**
     * Query public methods
     *
     * @param string $method method name
     * @param array $request request parameters
     * @return array request result on success
     * @throws \Exception
     */
    private function public($method, array $request = array())
    {
        // build the POST data string
        $postdata = http_build_query($request, '', '&');
        // make request
        curl_setopt($this->curl, CURLOPT_URL, $this->url . '/' . $this->version . '/public/' . $method);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());
        $result = curl_exec($this->curl);
        if($result===false)
            throw new \Exception('CURL error: ' . curl_error($this->curl));
        // decode results
        $result = json_decode($result, true);
        if(!is_array($result))
            throw new \Exception('JSON decode error');
        return $result;
    }
    /**
     * Query private methods
     *
     * @param string $method method path
     * @param array $request request parameters
     * @return array request result on success
     * @throws \Exception
     */
    private function private($method, array $request = array()) {
        if(!isset($request['nonce'])) {
            // generate a 64 bit nonce using a timestamp at microsecond resolution
            // string functions are used to avoid problems on 32 bit systems
            $nonce = explode(' ', microtime());
            $request['nonce'] = $nonce[1] . str_pad(substr($nonce[0], 2, 6), 6, '0');
        }
        // build the POST data string
        $postdata = http_build_query($request, '', '&');
        // set API key and sign the message
        $path = '/' . $this->version . '/private/' . $method;
        $sign = hash_hmac('sha512', $path . hash('sha256', $request['nonce'] . $postdata, true), base64_decode($this->secret), true);
        $headers = array(
            'API-Key: ' . $this->key,
            'API-Sign: ' . base64_encode($sign)
        );
        // make request
        curl_setopt($this->curl, CURLOPT_URL, $this->url . $path);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($this->curl);
        if($result===false)
            throw new \Exception('CURL error: ' . curl_error($this->curl));
        // decode results
        $result = json_decode($result, true);
        if(!is_array($result))
            throw new \Exception('JSON decode error');
        return $result;
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }
}
