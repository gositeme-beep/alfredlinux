<?php
/**
 * The Commons API - Social Connection & Dialogue
 */

$db = getDBConnection();

switch ($request_method) {
    case 'GET':
        if ($action === 'posts') {
            // Get posts feed
            $village_id = $_GET['village_id'] ?? null;
            $limit = min((int)($_GET['limit'] ?? 20), 100);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $sql = "SELECT p.*, u.username, u.display_name, u.avatar_url, v.name as village_name
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    LEFT JOIN villages v ON p.village_id = v.id
                    WHERE p.deleted_at IS NULL AND p.visibility = 'public'";
            
            $params = [];
            if ($village_id) {
                $sql .= " AND p.village_id = ?";
                $params[] = $village_id;
            }
            
            $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $posts = $stmt->fetchAll();
            
            // Get reaction counts
            foreach ($posts as &$post) {
                $reactionStmt = $db->prepare("SELECT COUNT(*) as count FROM reactions WHERE target_type = 'post' AND target_id = ?");
                $reactionStmt->execute([$post['id']]);
                $post['reaction_count'] = $reactionStmt->fetch()['count'];
                
                $commentStmt = $db->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = ? AND deleted_at IS NULL");
                $commentStmt->execute([$post['id']]);
                $post['comment_count'] = $commentStmt->fetch()['count'];
            }
            
            jsonResponse(['posts' => $posts, 'count' => count($posts)]);
        }
        
        if ($action === 'events') {
            // Get events
            $village_id = $_GET['village_id'] ?? null;
            $upcoming = isset($_GET['upcoming']) ? (bool)$_GET['upcoming'] : true;
            
            $sql = "SELECT e.*, u.username, u.display_name, v.name as village_name
                    FROM events e
                    JOIN users u ON e.user_id = u.id
                    LEFT JOIN villages v ON e.village_id = v.id
                    WHERE e.is_public = 1";
            
            $params = [];
            if ($village_id) {
                $sql .= " AND e.village_id = ?";
                $params[] = $village_id;
            }
            
            if ($upcoming) {
                $sql .= " AND e.start_date >= NOW()";
            }
            
            $sql .= " ORDER BY e.start_date ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll();
            
            // Get attendee counts
            foreach ($events as &$event) {
                $attendeeStmt = $db->prepare("SELECT COUNT(*) as count FROM event_attendees WHERE event_id = ? AND status = 'attending'");
                $attendeeStmt->execute([$event['id']]);
                $event['attendee_count'] = $attendeeStmt->fetch()['count'];
            }
            
            jsonResponse(['events' => $events, 'count' => count($events)]);
        }
        
        errorResponse('Invalid action', 400);
        break;
        
    case 'POST':
        // Authentication required for POST
        // TODO: Implement authentication
        errorResponse('Authentication required', 401);
        break;
        
    default:
        errorResponse('Method not allowed', 405);
}

