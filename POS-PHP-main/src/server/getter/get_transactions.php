<?php

function get_transactions($conn)
{
    $sql = "
        SELECT
            t.transaction_id,
            t.transaction_id_txt,
            t.transaction_create_at,
            t.transaction_amount,
            t.transaction_total,
            t.transaction_cash,
            t.transaction_money_changes,
            t.item_id,
            i.item_id_txt,
            i.item_name,
            i.item_price
        FROM transactions AS t
        LEFT JOIN items AS i
            ON t.item_id = i.item_id
        ORDER BY t.transaction_id DESC
    ";

    $results = mysqli_query($conn, $sql);

    if (!$results) {
        error_log(
            "[POS] Gagal mengambil riwayat transaksi: " .
            mysqli_error($conn)
        );

        return [];
    }

    $transactions = [];

    while ($row = mysqli_fetch_assoc($results)) {
        $local_id = (int) $row["transaction_id"];

        // Detail transaksi baru disimpan di tabel transaction_details.
        $detail_sql = "
            SELECT
                detail_id,
                item_id,
                item_name,
                item_price,
                quantity,
                subtotal
            FROM transaction_details
            WHERE transaction_id = ?
            ORDER BY detail_id ASC
        ";

        $detail_stmt = mysqli_prepare($conn, $detail_sql);

        if (!$detail_stmt) {
            error_log(
                "[POS] Gagal menyiapkan detail transaksi: " .
                mysqli_error($conn)
            );

            return [];
        }

        mysqli_stmt_bind_param($detail_stmt, "i", $local_id);

        if (!mysqli_stmt_execute($detail_stmt)) {
            error_log(
                "[POS] Gagal mengambil detail transaksi: " .
                mysqli_stmt_error($detail_stmt)
            );

            mysqli_stmt_close($detail_stmt);
            return [];
        }

        $detail_result = mysqli_stmt_get_result($detail_stmt);
        $details = [];

        while ($detail = mysqli_fetch_assoc($detail_result)) {
            $details[] = [
                "detail_id" => (int) $detail["detail_id"],
                "item_id" => (int) $detail["item_id"],
                "item_name" => $detail["item_name"],
                "item_price" => (float) $detail["item_price"],
                "quantity" => (int) $detail["quantity"],
                "subtotal" => (float) $detail["subtotal"]
            ];
        }

        mysqli_stmt_close($detail_stmt);

        /*
         * Kompatibilitas dengan transaksi lama:
         * transaksi yang belum memiliki baris detail tetap menampilkan
         * satu produk dari tabel items.
         */
        if (count($details) === 0) {
            $details[] = [
                "detail_id" => null,
                "item_id" => (int) ($row["item_id"] ?? 0),
                "item_name" => $row["item_name"] ?? "Produk tidak tersedia",
                "item_price" => (float) ($row["item_price"] ?? 0),
                "quantity" => (int) ($row["transaction_amount"] ?? 0),
                "subtotal" => (float) ($row["transaction_total"] ?? 0)
            ];
        }

        $row["details"] = $details;
        $transactions[] = $row;
    }

    return $transactions;
}
