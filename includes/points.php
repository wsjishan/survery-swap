<?php

declare(strict_types=1);

function record_point_transaction(
    PDO $pdo,
    int $userId,
    string $type,
    int $points,
    string $reason,
    ?int $surveyId = null,
    ?int $relatedUserId = null
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO point_transactions (user_id, type, points, reason, survey_id, related_user_id)
         VALUES (:user_id, :type, :points, :reason, :survey_id, :related_user_id)'
    );

    $stmt->execute([
        'user_id' => $userId,
        'type' => $type,
        'points' => $points,
        'reason' => $reason,
        'survey_id' => $surveyId,
        'related_user_id' => $relatedUserId,
    ]);
}

function credit_points(
    PDO $pdo,
    int $userId,
    int $points,
    string $reason,
    ?int $surveyId = null,
    ?int $relatedUserId = null
): bool
{
    if ($points < 1) {
        return false;
    }

    $startedTransaction = false;
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $startedTransaction = true;
    }

    try {
        $update = $pdo->prepare('UPDATE users SET points = points + :points WHERE id = :id');
        $update->execute(['points' => $points, 'id' => $userId]);

        if ($update->rowCount() !== 1) {
            if ($startedTransaction) {
                $pdo->rollBack();
            }
            return false;
        }

        record_point_transaction($pdo, $userId, TX_TYPE_CREDIT, $points, $reason, $surveyId, $relatedUserId);

        if ($startedTransaction) {
            $pdo->commit();
        }

        return true;
    } catch (Throwable $e) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function debit_points(
    PDO $pdo,
    int $userId,
    int $points,
    string $reason,
    ?int $surveyId = null,
    ?int $relatedUserId = null
): bool
{
    if ($points < 1) {
        return false;
    }

    $startedTransaction = false;
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $startedTransaction = true;
    }

    try {
        $update = $pdo->prepare(
            'UPDATE users
             SET points = points - :deduct_points
             WHERE id = :id AND points >= :required_points'
        );
        $update->execute([
            'deduct_points' => $points,
            'required_points' => $points,
            'id' => $userId,
        ]);

        if ($update->rowCount() !== 1) {
            if ($startedTransaction) {
                $pdo->rollBack();
            }
            return false;
        }

        record_point_transaction($pdo, $userId, TX_TYPE_DEBIT, $points, $reason, $surveyId, $relatedUserId);

        if ($startedTransaction) {
            $pdo->commit();
        }

        return true;
    } catch (Throwable $e) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
