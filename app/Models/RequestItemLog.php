<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class RequestItemLog extends Model
{
    protected $table = 'request_item_logs';
    protected $fillable = ['request_item_id', 'type', 'message'];

    const TYPE_EMERGENCY    = 'emergency';
    const TYPE_ALERT        = 'alert';
    const TYPE_CRITICAL     = 'critical';
    const TYPE_ERROR        = 'error';
    const TYPE_WARNING      = 'warning';
    const TYPE_NOTICE       = 'notice';
    const TYPE_INFO         = 'info';
    const TYPE_DEBUG        = 'debug';
    const TYPE_LOG          = 'log';

    /**
     * Get the default log type.
     *
     * @return string
     */
    public static function getDefaultType() {
        return static::TYPE_LOG;
    }

    /**
     * Get available log types.
     *
     * @return array
     */
    public static function getLogTypes() {
        return [
            static::TYPE_EMERGENCY,
            static::TYPE_ALERT,
            static::TYPE_CRITICAL,
            static::TYPE_ERROR,
            static::TYPE_WARNING,
            static::TYPE_NOTICE,
            static::TYPE_INFO,
            static::TYPE_DEBUG,
            static::TYPE_LOG,
        ];
    }

    /**
     * Determine if the log type is valid or not.
     *
     * @param $type
     * @return bool
     */
    public static function isValidType($type) {
        return in_array($type, static::getLogTypes());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function requestItem() {
        return $this->belongsTo(RequestItem::class);
    }

    /**
     * Log a new emergency message.
     *
     * @param RequestItem $requestItem
     * @param string $message
     * @return bool
     */
    public static function emergency(RequestItem $requestItem, $message) {
        return static::addMessage($requestItem, $message, __FUNCTION__);
    }

    /**
     * Log a new alert message.
     *
     * @param RequestItem $requestItem
     * @param string $message
     * @return bool
     */
    public static function alert(RequestItem $requestItem, $message) {
        return static::addMessage($requestItem, $message, __FUNCTION__);
    }

    /**
     * Log a new critical message.
     *
     * @param RequestItem $requestItem
     * @param string $message
     * @return bool
     */
    public static function critical(RequestItem $requestItem, $message) {
        return static::addMessage($requestItem, $message, __FUNCTION__);
    }

    /**
     * Log a new error message.
     *
     * @param RequestItem $requestItem
     * @param string $message
     * @return bool
     */
    public static function error(RequestItem $requestItem, $message) {
        return static::addMessage($requestItem, $message, __FUNCTION__);
    }

    /**
     * Log a new warning message.
     *
     * @param RequestItem $requestItem
     * @param string $message
     * @return bool
     */
    public static function warning(RequestItem $requestItem, $message) {
        return static::addMessage($requestItem, $message, __FUNCTION__);
    }

    /**
     * Log a new notice message.
     *
     * @param RequestItem $requestItem
     * @param string $message
     * @return bool
     */
    public static function notice(RequestItem $requestItem, $message) {
        return static::addMessage($requestItem, $message, __FUNCTION__);
    }

    /**
     * Log a new info message.
     *
     * @param RequestItem $requestItem
     * @param string $message
     * @return bool
     */
    public static function info(RequestItem $requestItem, $message) {
        return static::addMessage($requestItem, $message, __FUNCTION__);
    }

    /**
     * Log a new debug message.
     *
     * @param RequestItem $requestItem
     * @param string $message
     * @return bool
     */
    public static function debug(RequestItem $requestItem, $message) {
        return static::addMessage($requestItem, $message, __FUNCTION__);
    }

    /**
     * Log a new log message.
     *
     * @param RequestItem $requestItem
     * @param string $message
     * @return bool
     */
    public static function log(RequestItem $requestItem, $message) {
        return static::addMessage($requestItem, $message, __FUNCTION__);
    }

    /**
     * @param RequestItem $requestItem
     * @param string $message
     * @param null|string $type
     * @return bool
     */
    protected static function addMessage(RequestItem $requestItem, $message, $type = null) {
        $success = false;

        // Set the default type if not present.
        if( $type === null || !static::isValidType($type) ) { $type = static::getDefaultType(); }

        try {
            $logItem = new static([
                'request_item_id'   => $requestItem->id,
                'type'              => $type,
                'message'           => $message,
            ]);

            $success = $logItem->save();

            // Add the message to the application log as well.
            Log::$type($message, [
                'request_item_id'       => $requestItem->getKey(),
                'request_item_log_id'   => $logItem->getKey(),
            ]);
        } catch(\Throwable $e) {
            Log::error($e->getMessage());
        }

        return $success;
    }

}
