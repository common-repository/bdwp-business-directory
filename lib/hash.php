<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bdwp_alphabet_partition($list, $p)
{
    $listlen = count($list);
    $partlen = floor($listlen / $p);
    $partrem = $listlen % $p;
    $partition = array();
    $mark = 0;
    for ($px = 0; $px < $p; $px++) {
        $incr = ($px < $partrem) ? $partlen + 1 : $partlen;
        $partition[$px] = array_slice($list, $mark, $incr);
        $mark += $incr;
    }
    return $partition;
}

function bdwp_get_alphabet()
{
    $alphabet = get_option('bdwp-alphabet');
    if (!$alphabet) {
        $alphabet = array_merge(range('a', 'z'), range('0', '9'));
        shuffle($alphabet);
        $alphabet = implode('', $alphabet);
        add_option('bdwp-alphabet', $alphabet);
    }
    $alphabet = str_split($alphabet);
    $alphabet = bdwp_alphabet_partition($alphabet, 10);
    return $alphabet;
}

function bdwp_hash_encode($int, $min_length = 6)
{
    $alphabet = bdwp_get_alphabet();
    $count_before = $min_length - strlen($int);
    if ($count_before < 0) $count_before = 0;
    $int = str_repeat('0', $count_before) . $int;
    $int = str_split($int);
    $int = array_map(function ($elem, $id) use ($alphabet) {
        $letters = $alphabet[$elem];
        $id = $id % count($letters);
        return $letters[$id];
    }, $int, array_keys($int));
    return implode('', $int);
}

function bdwp_hash_decode($val)
{
    $alphabet = bdwp_get_alphabet();
    $val = str_split($val);
    $val = array_map(function ($sym) use ($alphabet) {
        foreach ($alphabet as $int => $letters) {
            if (in_array($sym, $letters)) {
                $sym = $int;
                break;
            }
        }
        return $sym;
    }, $val);
    $val = implode('', $val);
    $val = intval($val);
    return $val;
}