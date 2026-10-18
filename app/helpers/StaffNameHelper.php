<?php
/**
 * StaffNameHelper — keeps a teacher's name in sync across the system.
 *
 * Several tables link to a teacher by their full name text instead of by
 * staff_accounts.id, so renaming a teacher must update all of them or they
 * lose their classes, advisory sections, notes and reminders:
 *   classes.teacher, classes.requestor
 *   staff_advising.fullName
 *   teacher_notes.teacher_name, teacher_reminders.teacher_name
 * Chat messages store a display-name snapshot, matched by id + type.
 *
 * Call renameEverywhere() inside the same transaction that updates
 * staff_accounts, so a failure rolls everything back together.
 */
class StaffNameHelper
{
    /**
     * Returns true when another staff member already uses this full name.
     */
    public static function nameTaken(PDO $pdo, string $fullName, int $staffId): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM staff_accounts WHERE fullName = :fullName AND id <> :id LIMIT 1");
        $stmt->execute(['fullName' => $fullName, 'id' => $staffId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Returns the staff member's current full name, or null if not found.
     */
    public static function currentName(PDO $pdo, int $staffId): ?string
    {
        $stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :id");
        $stmt->execute(['id' => $staffId]);
        $name = $stmt->fetchColumn();
        return $name === false ? null : $name;
    }

    /**
     * Replaces $oldName with $newName in every table that links to the teacher by name.
     */
    public static function renameEverywhere(PDO $pdo, int $staffId, string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }

        $byName = [
            'UPDATE classes SET teacher = :new WHERE teacher = :old',
            'UPDATE classes SET requestor = :new WHERE requestor = :old',
            'UPDATE staff_advising SET fullName = :new WHERE fullName = :old',
            'UPDATE teacher_notes SET teacher_name = :new WHERE teacher_name = :old',
            'UPDATE teacher_reminders SET teacher_name = :new WHERE teacher_name = :old',
        ];
        foreach ($byName as $sql) {
            $pdo->prepare($sql)->execute(['new' => $newName, 'old' => $oldName]);
        }

        $byId = [
            "UPDATE messages SET sender_name = :new WHERE sender_type = 'staff' AND sender_id = :id",
            "UPDATE messages SET receiver_name = :new WHERE receiver_type = 'staff' AND receiver_id = :id",
        ];
        foreach ($byId as $sql) {
            $pdo->prepare($sql)->execute(['new' => $newName, 'id' => $staffId]);
        }
    }
}
