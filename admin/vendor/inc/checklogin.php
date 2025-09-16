<?php
/**
 * Centralized auth guard for KAYA
 * - Use require_admin() on admin pages -> returns a_id (int) or redirects.
 * - Use require_user() on driver/user pages -> returns u_id (int) or redirects.
 * - Use check_login('any') if a page is shared by both and either is OK.
 */

function check_login(string $required = 'admin') {
    // Always have a session
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Small helper to nuke the session and redirect
    $redirect = function (string $to = 'index.php') {
        // Clear session data
        $_SESSION = [];
        if (session_id() !== '') {
            // Invalidate session cookie if used
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
        }

        // Build a URL to "index.php" in the current directory (works for /admin/*)
        $host = $_SERVER['HTTP_HOST'];
        $dir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        header("Location: http://{$host}{$dir}/{$to}");
        exit;
    };

    $type = $_SESSION['user_type'] ?? null;

    if ($required === 'admin') {
        // Must be admin and have a_id
        if ($type !== 'admin' || empty($_SESSION['a_id'])) {
            $redirect('index.php'); // change to 'admin-login.php' if you have a dedicated admin login
        }
        return (int) $_SESSION['a_id'];
    }

    if ($required === 'user') {
        // Must be user/driver and have u_id
        if ($type !== 'user' || empty($_SESSION['u_id'])) {
            $redirect('index.php');
        }
        return (int) $_SESSION['u_id'];
    }

    // 'any' -> allow either admin or user
    if ($type === 'admin' && !empty($_SESSION['a_id'])) return (int) $_SESSION['a_id'];
    if ($type === 'user'  && !empty($_SESSION['u_id'])) return (int) $_SESSION['u_id'];
    $redirect('index.php');
}

/** Sugar helpers for clearer calls on pages */
function require_admin() { return check_login('admin'); }
function require_user()  { return check_login('user');  }

/** Optional helpers if you need quick checks without redirecting */
function is_admin() { return isset($_SESSION['user_type'], $_SESSION['a_id']) && $_SESSION['user_type'] === 'admin' && $_SESSION['a_id']; }

// --- Map legacy admin session to accounts.id once per session ---
if (isset($_SESSION['a_id']) && empty($_SESSION['accounts_id'])) {
  $aid = (int)$_SESSION['a_id'];

  // Try to find a matching accounts row by email from tms_admin
  if ($st = $mysqli->prepare("
        SELECT a_email, COALESCE(NULLIF(a_name,''),'Admin') AS nm, a_pwd
        FROM tms_admin WHERE a_id=? LIMIT 1
      ")) {
    $st->bind_param('i', $aid);
    $st->execute();
    $st->bind_result($email, $name, $pwd);
    if ($st->fetch() && $email) {
      $st->close();

      // Look up accounts.id
      if ($f = $mysqli->prepare("SELECT id FROM accounts WHERE email=? LIMIT 1")) {
        $f->bind_param('s', $email);
        $f->execute();
        $f->bind_result($acctId);
        if ($f->fetch()) {
          $_SESSION['accounts_id'] = (int)$acctId;
          $f->close();
        } else {
          $f->close();
          // Create one if missing (reuses the existing hash from tms_admin)
          if ($i = $mysqli->prepare("
                INSERT INTO accounts(role,name,email,password_hash,is_active,created_at,updated_at)
                VALUES('admin',?,?,?,1,NOW(),NOW())
              ")) {
            $i->bind_param('sss', $name, $email, $pwd);
            $i->execute();
            $_SESSION['accounts_id'] = (int)$i->insert_id;
            $i->close();
          }
        }
      }
    } else {
      $st->close();
    }
  }
}

function is_user()  { return isset($_SESSION['user_type'], $_SESSION['u_id']) && $_SESSION['user_type'] === 'user'  && $_SESSION['u_id']; }
