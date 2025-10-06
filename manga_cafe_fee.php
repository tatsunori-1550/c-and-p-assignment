<?php

// マンガ喫茶の料金計算処理
class MangaCafeCalculator
{
    private const TAX_RATE = 0.1; // 税率10%
    private const LATE_NIGHT_RATE = 0.15; // 深夜割増15%

    public static function calculate(
        DateTimeImmutable $enter,
        DateTimeImmutable $exit,
        string $course
    ): array {
        // コース料金表（税抜）
        $prices = [
            '通常' => 500, // 1時間
            '3時間パック' => 800,
            '5時間パック' => 1500,
            '8時間パック' => 1900,
        ];

        // コースごとの利用時間（分）
        $courseTimes = [
            '通常' => 60,
            '3時間パック' => 180,
            '5時間パック' => 300,
            '8時間パック' => 480,
        ];

        // 経過時間（分）
        $diffMinutes = ($exit->getTimestamp() - $enter->getTimestamp()) / 60;

        // 基本料金
        $base = $prices[$course] ?? 0;

        // 延長料金（10分ごと100円）
        $overMinutes = max(0, $diffMinutes - $courseTimes[$course]);
        $extraCharge = ceil($overMinutes / 10) * 100;

        //======================
        // 深夜割増の判定（22:00〜翌5:00）
        // ======================
        $hasLateNight = self::isLateNight($enter, $exit);

        if ($hasLateNight && $extraCharge > 0) {
            $extraCharge *= (1 + self::LATE_NIGHT_RATE); // 15%割増
        }

        // 小計
        $subtotal = $base + $extraCharge;

        // 税込
        $total = $subtotal * (1 + self::TAX_RATE);

        return [
            'コース' => $course,
            '入店' => $enter->format('Y-m-d H:i'),
            '退店' => $exit->format('Y-m-d H:i'),
            '経過時間(分)' => round($diffMinutes),
            '延長料金(税抜)' => $extraCharge,
            '深夜割増対象' => $hasLateNight ? 'あり' : 'なし',
            '合計(税込)' => round($total),
        ];
    }

    /**
     * 深夜時間（22:00〜翌5:00）に1分でも含まれるかチェック
     */

    private static function isLateNight(DateTimeImmutable $enter, DateTimeImmutable $exit): bool
    {
       $current = $enter;
       while ($current <= $exit) {
        $hour = (int)$current->format('H');
        if ($hour >= 22 || $hour <5) {
            return true; // 深夜時間含む
        }
        // 10分ずつ進めてチェック（パフォーマンス考慮）
        $current = $current->modify('+10 minutes');
       } 
       return false;
    }
}

// 動作テスト
$enter = new DateTimeImmutable('2025-10-05 21:00');
$exit = new DateTimeImmutable('2025-10-06 23:45');
$result = MangaCafeCalculator::calculate($enter, $exit, '3時間パック');
print_r($result);