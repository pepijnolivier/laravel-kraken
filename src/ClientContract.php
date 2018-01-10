<?php
namespace Pepijnolivier\Kraken;

interface ClientContract
{

    // ------------------------------------- PUBLIC API METHODS -------------------------------------------

    /**
     * Note: This is to aid in approximating the skew time between the server and client.
     * @return array Servers' time
     */
    public function getServerTime();

    /**
     * Get asset info
     *
     * @return array of asset names and their info
     */
    public function getAssetInfo();

    /**
     * Get tradable asset pairs
     *
     * @param array $pairs
     * @param string $info (all, leverage, fees, margin)
     * @return array of pair names and their info
     */
    public function getAssetPairs(array $pairs=null, string $info='info');

    /**
     * Get tickers information
     *
     * @param array $pairs
     * @return array of pair names and their ticker info
     */
    public function getTickers(array $pairs);


    /**
     * Get OHLC Data
     *
     * @param string $currencypair
     * @param int $timeFrameIntervalMinutes
     * @param string $sinceId return committed OHLC data since given id (optional.  exclusive)
     * @return array
     */
    public function getTicker(string $currencypair, int $timeFrameIntervalMinutes=1, string $sinceId=null);

    /**
     * Get order book
     *
     * @param string $pair asset pair to get market depth for
     * @param int|null $count maximum number of asks/bids (optional)
     * @return array
     */
    public function getOrderBook(string $pair, int $count=null);

    /**
     * Get recent trades
     *
     * @param string $pair asset pair to get trade data for
     * @param string|null $sinceId return trade data since given id (optional.  exclusive)
     * @return array
     */
    public function getRecentTrades(string $pair, string $sinceId=null);

    /**
     * Get recent spread data
     * Note: "since" is inclusive so any returned data with the same time as the previous set
     * should overwrite all of the previous set's entries at that time
     *
     * @param string $pair asset pair to get spread data for
     * @param string|null $sinceId return spread data since given id (optional.  inclusive)
     * @return array
     */
    public function getRecentSpreads(string $pair, string $sinceId=null);



    // ------------------------------------- PRIVATE API METHODS -------------------------------------------


    /**
     * Get balances
     *
     * @return array of asset names and balance amount
     */
    public function getBalances();


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
    public function getTradeBalance(string $currency, string $baseCurrency, string $assetClass=null);

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
    public function getOpenOrders(bool $includeTrades=false, string $userRef=null);


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
     * @param string|null $userRef  restrict results to given user reference id (optional)
     * @return array of order info
     */
    public function getClosedOrders(int $startUnixTimestamp=null, int $endUnixTimestamp=null, int $offset=null, string $closeTime='both', bool $includeTrades=false, string $userRef=null);


    /**
     * Get order info
     *
     * @param array $transactionIds (max 20)
     * @param bool $includeTrades whether or not to include trades in output (optional.  default = false)
     * @param string|null $userRef restrict results to given user reference id (optional)
     * @return array containing orders info
     */
    public function getOrdersInfo(array $transactionIds, bool $includeTrades=false, string $userRef=null);

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
    public function getTradesHistory(string $type='all', int $startUnixTimestamp=null, int $endUnixTimestamp=null, int $offset=null, bool $includeTrades=false);

    /**
     * Get trades info
     *
     * @param array $transactionIds list of transaction ids to query info about (20 maximum)
     * @param bool $includeRelatedTrades whether or not to include trades related to position in output (optional.  default = false)
     * @return array of trades info
     */
    public function getTradesInfo(array $transactionIds, bool $includeRelatedTrades=false);

    /**
     * Get open positions
     * See https://www.kraken.com/help/api#get-open-positions
     *
     * @param array $transactionIds list of transaction ids to restrict output to
     * @param bool $calculateProfitLoss whether or not to include profit/loss calculations (optional.  default = false)
     * @return array of open position info
     */
    public function getOpenPositions(array $transactionIds, bool $calculateProfitLoss=false);

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
    public function getLedgers(string $currency, int $startUnixTimestamp=null, int $endUnixTimestamp=null, int $offset=null, string $type='all', string $assetClass=null);

    /**
     * @param array $ledgerIds list of ledger ids to query info about (20 maximum)
     * @return array of ledgers info
     */
    public function getLedgersInfo(array $ledgerIds);


    /**
     * Get trade volume
     * See https://www.kraken.com/help/api#get-trade-volume
     *
     * @param array $currencypairs list of asset pairs to get fee info on (optional)
     * @param bool $includeFeeInfo  whether or not to include fee info in results (optional)
     * @return array
     */
    public function getTradeVolume(array $currencypairs=null, bool $includeFeeInfo=false);

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
     * @return array
     */
    public function addOrder(string $currencypair, string $buyOrSell, string $orderType, string $volume, string $price, string $price2=null, $leverage='none', array $oflags=[], string $startTm=null, string $expireTm=null, string $userRef=null, bool $validateOnly=false);


    /**
     * @param string $transactionId
     * @return array
     */
    public function cancelOrder(string $transactionId);


    /**
     * Limit BUY
     *
     * @param string $currencypair
     * @param string $quantity
     * @param string $rate
     * @return array
     */
    public function buy(string $currencypair, string $quantity, string $rate, $options=[]);


    /**
     * Limit SELL
     *
     * @param string $currencypair
     * @param string $quantity
     * @param string $rate
     * @return array
     */
    public function sell(string $currencypair, string $quantity, string $rate, $options=[]);


    /**
     * @param string $currency
     * @param string|null $assetClass
     * @return array
     */
    public function getDepositMethods(string $currency, string $assetClass=null);

    /**
     * @param string $currency
     * @param string $methodName
     * @param bool $new
     * @param string|null $assetClass
     * @return array of deposit addresses
     */
    public function getDepositAddresses(string $currency, string $method, bool $new=false, string $assetClass=null);

    /**
     * See https://www.kraken.com/help/api#deposit-status
     *
     * @param string $currency
     * @param string $methodName
     * @param string|null $assetClass
     * @return array of deposit status information
     */
    public function getDepositStatus(string $currency, string $method, string $assetClass=null);


    /**
     * @param string $currency
     * @param string $key
     * @param string $amount
     * @param string|null $assetClass
     * @return array of withdrawal info
     */
    public function getWithdrawInfo(string $currency, string $key, string $amount, string $assetClass=null);

    /**
     * @param string $currency
     * @param string $key
     * @param string $amount
     * @param string|null $assetClass
     * @return array of withdrawal transactio
     */
    public function withdraw(string $currency, string $key, string $amount, string $assetClass=null);

    /**
     * See https://www.kraken.com/help/api#withdraw-status
     *
     * @param string $currency
     * @param string $method
     * @param string|null $assetClass
     * @return array of withdrawal status information
     */
    public function getWithdrawalStatus(string $currency, string $method, string $assetClass=null);


    /**
     * Cancellation cannot be guaranteed. This will put in a cancellation request.
     * Depending upon how far along the withdrawal process is, it may not be possible to cancel the withdrawal.

     * @param string $currency
     * @param string $referenceId
     * @param string|null $assetClass
     *
     * @return mixed
     */
    public function cancelWithdrawal(string $currency, string $referenceId, string $assetClass=null);


}
