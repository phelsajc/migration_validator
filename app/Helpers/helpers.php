<?php
    function calculatePayorDiscountPercent($amount,$pd){
        $value = ($pd/$amount) * 100;
        return $value;
    }

    
    function calculatePayorDiscountAmount($amount,$pd){
        $value = ($pd / $amount) * 100;
        $total = ($value/100) * $amount;
        return $total;
    }
?>