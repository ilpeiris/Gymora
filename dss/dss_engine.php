<?php
// /Gymora/dss/dss_engine.php
require_once dirname(__DIR__) . '/config/db.php';



/**
 * Core Decision Support System (DSS) Logic Engine
 * Evaluates a user's active medical conditions against system rules.
 * * @param int $user_id The ID of the user to evaluate
 * @return array Arrays of blocked exercises, warned exercises, and the reasoning.
 */




function getDSSRestrictionsForUser($user_id) {
    global $pdo;
    
    // Initialize our empty restriction lists
    $restrictions = [
        'blocked_exercise_ids' => [],
        'warned_exercise_ids' => [],
        'reasons' => [],
        'alternatives' => []
    ];

    try {
        // 1. Fetch the user's ACTIVE medical conditions from their latest assessment
        $condStmt = $pdo->prepare("
            SELECT c.condition_name, c.severity 
            FROM medical_conditions c
            JOIN medical_assessments a ON c.assessment_id = a.id
            WHERE a.user_id = ? AND a.status = 'submitted' AND c.is_active = 1
        ");
        $condStmt->execute([$user_id]);
        $active_conditions = $condStmt->fetchAll(PDO::FETCH_ASSOC);

        // If they have no conditions, return the empty arrays (they are cleared to train)
        if (count($active_conditions) === 0) {
            return $restrictions;
        }

        // 2. Loop through each condition and ask the Rule Engine what to do
        $ruleStmt = $pdo->prepare("
            SELECT exercise_id, rule_type, reason, alternative_exercise_id 
            FROM dss_rules 
            WHERE condition_name = ? AND severity_threshold <= ?
        ");

        foreach ($active_conditions as $condition) {
            $ruleStmt->execute([$condition['condition_name'], $condition['severity']]);
            $triggered_rules = $ruleStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 3. Process the triggered rules
            foreach ($triggered_rules as $rule) {
                $ex_id = $rule['exercise_id'];
                
                if ($rule['rule_type'] === 'BLOCK') {
                    $restrictions['blocked_exercise_ids'][] = $ex_id;
                } elseif ($rule['rule_type'] === 'WARN') {
                    $restrictions['warned_exercise_ids'][] = $ex_id;
                }
                
                // Save the reason so we can show it to the trainer/user
                $restrictions['reasons'][$ex_id] = $rule['reason'];
                
                // Save the alternative exercise suggestion if one exists
                if ($rule['alternative_exercise_id']) {
                    $restrictions['alternatives'][$ex_id] = $rule['alternative_exercise_id'];
                }
            }
        }
        
        // Remove duplicate IDs just in case multiple conditions block the same exercise
        $restrictions['blocked_exercise_ids'] = array_unique($restrictions['blocked_exercise_ids']);
        $restrictions['warned_exercise_ids'] = array_unique($restrictions['warned_exercise_ids']);
        
        return $restrictions;

    } catch (PDOException $e) {
        error_log("DSS Engine Error: " . $e->getMessage());
        return $restrictions; // Fails safe (allows exercise if DB crashes, though in a real medical app we might fail strict)
    }
}
?>