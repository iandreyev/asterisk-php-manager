<?php

/**
 * This package is capable of interfacing with the open source Asterisk PBX via 
 * its built in Manager API.  This will allow you to execute manager commands
 * for administration and maintenance of the server.
 * 
 * @category
 * @package AsteriskInterface
 * @author Doug Bromley <doug.bromley@gmail.com>
 * @copyright Doug Bromley 2008
 * @license GPL v3
 * @version 1.0
 * @link http://www.straw-dogs.co.uk
 * PHP5 only
 *
 * Copyright (c) 2008, Doug Bromley <doug.bromley@gmail.com>
 * All rights reserved.
 *
 ***
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, 
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, 
 *   this list of conditions and the following disclaimer in the documentation 
 *   and/or other materials provided with the distribution.
 * - Neither the name of the <ORGANIZATION> nor the names of its 
 *   contributors may be used to endorse or promote products derived from 
 *   this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, 
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR 
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, 
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, 
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, 
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, 
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 ***
 *
 */

/**
 * Class for accessing the Asterisk Manager interface {@link }
 *
 */
class AsteriskInterface
{
    /**
     * The Asterisk server which will recieve the manager commands 
     * @access public
     * @var string
     * @uses __construct
     */
    public $server;

    /**
     * The port to use when connecting to the Asterisk server
     * @access public
     * @var integer
     * @uses __construct
     */
    public $port = 5038;

    /**
     * The username to access the Asterisk manager interface
     * @access public
     * @var string
     * @uses __construct, login
     */
    public $username;

    /**
     * The password used to access the Asterisk manager interface
     * @access public
     * @var string
     * @uses __construct, login
     */
    public $password;
    
    /**
     * The opened socket to the Asterisk server
     * @access private 
     * @var object
     * @uses __construct, login
     */
    private $socket;

    function __construct($server, $username, $password, $port = 5038)
    {
        $this->server = $server;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;

        if($this->socket) {
            $this->close();
        }

        if($this->socket = fsockopen($this->server, $this->port)) {
            stream_set_timeout($this->socket, 3);
            if(!self::login()) {
                $this->error = 'Authentication failure';
                $this->close();
            }
        } else {
            $this->error = 'Could not establish connection';
            return false;
        }
    }

    function login()
    {
        fputs($this->socket, "Action: login\r\n");
        fputs($this->socket, "Username: {$this->username}\r\n");
        fputs($this->socket, "Secret: {$this->password}\r\n\r\n");
        $response = stream_get_contents($this->socket);
        if(strpos($response, "Message: Authentication accepted") != FALSE) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Logout of the current manager session attached to $this::socket
     * @access public
     * @return bool
     */
    function logout()
    {
        if($this->socket) {
            fputs($this->socket, "Action: Logoff\r\n\r\n");
            fclose($this->socket);
            return true;
        }
        return false;
    }

    /**
     * Just kill the connection without logging off
     * @access public
     * @return bool
     */
    function close()
    {
        if($this->socket) {
            return fclose($this->socket);
        }

        return false;
    }

    /**
     * Send a command to the Asterisk CLI interface
     * @param string $command
     * @param bool $output Do you want the response to be echoed. If not it'll be returned
     */
    function command($command, $output = false)
    {
        if($this->socket) {
            fputs($this->socket, "Action: Command\r\n");
            fputs($this->socket, "Command: $command\r\n\r\n");
            
            if($output) {
                echo stream_get_contents($this->socket);
            } else {
                return fgets($this->socket);
            }
        }
        return false;
    }

    /**
     * Make a call to an extension with a given channel acting as the originator
     * @param string $extension The number to dial
     * @param string $channel The channel string of the originating handset
     * @param string $context The context that the call will be dropped into 
     * @param integer $priority The priority of this command
     */
    function originateCall($extension, $channel, $context, $priority = 1)
    {
        if($this->socket) {
            
        }
    }

    /**
     * Add a handset to a queue on the server
     * @param string $queue The name of the queue you wish to add the handset too
     * @param string The handset you wish to add.  Must include the protocol type, e.g. SIP/234
     * @return bool
     */
    function queueAdd($queue, $handset, $penalty)
    {
        if($this->socket) {
            fputs($this->socket, "Action: QueueAdd\r\n");
            fputs($this->socket, "Queue: $queue\r\n");
            fputs($this->socket, "Interface: $handset\r\n");
            if($penalty) {
                fputs($this->socket, "Penalty: $penalty\r\n\r\n");
            } else {
                fputs($this->socket, "\r\n");
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Remove a handset from the given queue
     * @param string $queue The queue you wish to perform this action on
     * @param string $handset The handset you wish to remove (e.g. SIP/200)
     * @return bool
     */
    function queueRemove($queue, $handset) 
    {
        if($this->socket) {
            fputs($this->socket, "Action: QueueRemove\r\n");
            fputs($this->socket, "Queue: $queue\r\n");
            fputs($this->socket, "Interface: $handset\r\n\r\n");
            return true;
        } else {
            return false;
        }
    }

    /**
     * Monitor(record) a channel to given file in given format
     * @param string $channel Channel to monitor (e.g. SIP/234, ZAP/1)
     * @param string $filename The filename to save to
     * @param string $format The format of the file (e.g. gsm, wav)
     * @param integer $mix
     */
    function monitor($channel, $filename, $format, $mix = null)
    {
        if($this->socket) {
            fputs($this->socket, "Action: Monitor\r\n");
            fputs($this->socket, "Channel: $channel\r\n");
            fputs($this->socket, "File: $filename\r\n");
            fputs($this->socket, "Format: $format\r\n");
            fputs($this->socket, "Mix: $mix\r\n\r\n");
            
            $response = stream_get_contents($this->socket);

            if(strpos($response, "Success") === FALSE) {
                $this->error = 'Failed to monitor channel';
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Get the status information for a channel
     * @param string $channel The channel to query.
     * @return string|string
     */
    function status($channel = null)
    {
        if($this->socket) {
            fputs($this->socket, "Action: Status\r\n");
            fputs($this->socket, "Channel: $channel\r\n\r\n");
            $response = stream_get_contents($this->socket);
            return $response;
        } else {
            return false;
        }
    }

    /**
     * Get a list of SIP peers and their status
     * return string|bool
     */
    function sipPeers()
    {
        if($this->socket) {
            fputs($this->socket, "Action: Sippeers");
            $response = stream_get_contents($this->socket);
            return $reponse;
        } else {
            return false;
        }
    }
}

?>
