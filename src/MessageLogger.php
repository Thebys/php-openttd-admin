<?php

namespace Thebys\PhpOpenttdStats;

class MessageLogger {
    private $messages = [];
    private $maxMessages = 1000; // Limit the number of stored messages
    
    public function log($type, $direction, $data, $decoded = null) {
        $message = [
            'timestamp' => microtime(true),
            'type' => $type,
            'direction' => $direction,
            'raw_data' => $this->sanitizeData($data),
            'decoded' => $this->sanitizeData($decoded)
        ];
        
        array_unshift($this->messages, $message);
        
        // Keep only the last maxMessages
        if (count($this->messages) > $this->maxMessages) {
            array_pop($this->messages);
        }
    }
    
    public function getMessages($limit = null) {
        if ($limit === null) {
            return $this->messages;
        }
        return array_slice($this->messages, 0, $limit);
    }
    
    public function clear() {
        $this->messages = [];
    }

    /**
     * Sanitizes data for JSON encoding
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    private function sanitizeData($data) {
        if ($data === null) {
            return null;
        }

        if (is_array($data)) {
            return array_map([$this, 'sanitizeData'], $data);
        }

        if (is_string($data)) {
            // Convert non-UTF8 characters to hex representation
            $clean = '';
            $length = strlen($data);
            
            for ($i = 0; $i < $length; $i++) {
                $char = $data[$i];
                $ord = ord($char);
                
                if ($ord < 32 || $ord > 126) {
                    $clean .= sprintf('\\x%02x', $ord);
                } else {
                    $clean .= $char;
                }
            }
            
            return $clean;
        }

        return $data;
    }
    
    /**
     * Format message for display
     * @param array $message Message to format
     * @return string Formatted message
     */
    public function formatMessage($message) {
        $time = date('Y-m-d H:i:s', (int)$message['timestamp']);
        $ms = sprintf(".%03d", ($message['timestamp'] - floor($message['timestamp'])) * 1000);
        
        $arrow = $message['direction'] === 'sent' ? '>>' : '<<';
        
        $formatted = [
            'time' => "{$time}{$ms}",
            'direction' => $arrow,
            'type' => $message['type'],
            'raw_data' => $message['raw_data']
        ];

        if ($message['decoded'] !== null) {
            $formatted['decoded'] = $message['decoded'];
        }

        return $formatted;
    }

    /**
     * Get messages formatted for display
     * @param int|null $limit Optional limit of messages to return
     * @return array Formatted messages
     */
    public function getFormattedMessages($limit = null) {
        $messages = $this->getMessages($limit);
        return array_map([$this, 'formatMessage'], $messages);
    }

    /**
     * Convert messages to JSON string
     * @param int|null $limit Optional limit of messages to return
     * @return string JSON encoded messages
     */
    public function toJson($limit = null) {
        return json_encode($this->getFormattedMessages($limit), 
            JSON_UNESCAPED_UNICODE | 
            JSON_UNESCAPED_SLASHES | 
            JSON_PRETTY_PRINT
        );
    }
} 