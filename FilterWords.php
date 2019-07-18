<?php

/**
 * 关键词过滤类
 * @author RUI
 * @DateTime 2018-02-11
 */
class FilterWords
{
    /**
     * 替换码
     * @var string
     */
    private $replaceCode = '*';

    /**
     * 敏感词库集合
     * @var array
     */
    private $trieTreeMap = [];

    /**
     * 干扰因子集合
     * @var array
     */
    private $disturbList = [];

    public function __construct($disturbList = []) {
        $this->disturbList = $disturbList;
    }

    /**
     * 添加敏感词
     * @param array $wordsList
     */
    public function addWords(array $wordsList) {
        foreach ($wordsList as $item) {
            $nowWords = &$this->trieTreeMap;
            $words = $item['words'];
            $len = mb_strlen($words);
            for ($i = 0; $i < $len; $i++) {
                $word = mb_substr($words, $i, 1);
                if (!isset($nowWords[$word])) {
                    $nowWords[$word] = false;
                }
                if (($i === $len - 1)) {
                    $nowWords[$word]['trie'] = true;
                    $nowWords[$word]['replace_code'] = isset($item['replace_code']) ? $item['replace_code'] : $this->replaceCode;
                }
                $nowWords = &$nowWords[$word];
            }
        }
    }

    /**
     * 清空敏感词
     */
    public function clearWords() {
        $this->trieTreeMap = [];
    }

    /**
     * 查找对应敏感词
     * @param $txt
     * @return array
     */
    public function search($txt, $hasReplace=false, &$replaceCodeList = array()) {
        $wordsList = array();
        $txtLength = mb_strlen($txt);
        for ($i = 0; $i < $txtLength; $i++) {
            $result = $this->checkWord($txt, $i, $txtLength);
            if ($result['word_length'] > 0) {
                $words = mb_substr($txt, $i, $result['word_length']);

                //  避免重复添加
                if(!in_array($words, $wordsList)) {
                    $wordsList[] = $words;
                    $hasReplace && $replaceCodeList[] = str_repeat($result['replace_code'], mb_strlen($words));
                }

                $i += $result['word_length'] - 1;
            }
        }
        return $wordsList;
    }

    /**
     * 过滤敏感词
     * @param $txt
     * @return mixed
     */
    public function filter($txt) {
        $replaceCodeList = array();
        $wordsList = $this->search($txt, true, $replaceCodeList);

        if (empty($wordsList)) {
            return $txt;
        }
        return str_replace($wordsList, $replaceCodeList, $txt);
    }

    /**
     * 敏感词检测
     * @param $txt
     * @param $beginIndex
     * @param $length
     * @return array
     */
    private function checkWord($txt, $beginIndex, $length) {

        $flag = false;
        $wordLength = 0;
        $currentWordLength = 0;
        $trieTree = &$this->trieTreeMap;
        $replaceCode = '';

        for ($i = $beginIndex; $i < $length; $i++) {
            $word = mb_substr($txt, $i, 1);
            $wordLength++;

            // 检查是否有干扰因子
            if ($this->checkDisturb($word)) {
                continue;
            }

            // 如果配置则记录
            if(isset($trieTree[$word]['trie']) && $trieTree[$word]['trie'] === true) {
                $flag = true;
                $currentWordLength = $wordLength;
                $replaceCode = $trieTree[$word]['replace_code'];
            }

            // 没有配置的元素了
            if (!isset($trieTree[$word])) {
                break;
            }

            $trieTree = &$trieTree[$word];
        }
        $flag || $currentWordLength = 0;

        return ['word_length' => $currentWordLength, 'replace_code' => $replaceCode];
    }

    /**
     * 干扰因子检测
     * @param $word
     * @return bool
     */
    private function checkDisturb($word) {
        return in_array($word, $this->disturbList);
    }
}
