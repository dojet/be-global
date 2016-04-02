<?php
/**
 *
 * @author liyan
 * @since 2016 4 2
 */
class SOARegistry {

    public static function register($service, SOANode $node) {
        $nodeKey = self::serviceNodeKey($service, $node);
        $nodeJson = $node->toJson();
        DRedis::setex($nodeKey, 3, $nodeJson);

        $registerKey = self::serviceRegisterKey($service);
        DRedis::sAdd($registerKey, self::nodeKey($node));
    }

    public static function unregister($service, SOANode $node) {
        $registerKey = self::serviceRegisterKey($service);
        self::removeNodeKey($registerKey, self::nodeKey($node));
    }

    public static function getNode($service) {
        $registerKey = self::serviceRegisterKey($service);
        $nodeCount = DRedis::sCard($registerKey);
        for ($retry = 0; $retry < $nodeCount; $retry++) {
            $nodeKey = DRedis::sRandMember($registerKey);
            $nodeJson = DRedis::get($nodeKey);
            if (is_null($nodeJson)) {
                self::removeNodeKey($registerKey, $nodeKey);
                continue;
            }
            return SOANode::nodeFromJson($nodeJson);
        }
        throw new Exception("no available node", -1);
    }

    protected static function removeNodeKey($registerKey, $nodeKey) {
        DRedis::sRem($registerKey, $nodeKey);
    }

    public static function serviceKey($service) {
        return md5($service);
    }

    public static function nodeKey(SOANode $node) {
        return md5($node->toJson());
    }

    public static function serviceRegisterKey($service) {
        return sprintf("SOA:service:%s:nodes", self::serviceKey($service));
    }

    public static function serviceNodeKey($service, SOANode $node) {
        return sprintf("SOA:service:%s:node:%s",
            self::serviceKey($service), self::nodeKey($node));
    }

}
