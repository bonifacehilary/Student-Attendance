# EduAttend Assets

Static files for the **Student Attendance** system (not Mpemba).

| Path | Purpose |
|------|---------|
| `css/eduattend.css` | Shared styles |
| `js/student-app.js` | Student portal UI helpers |
| `js/admin-app.js` | Admin dashboard helpers |
| `js/api-client.js` | Attendance API client |
| `jquery/` | jQuery |
| `sweetalert2/` | Alerts |
| `bootstrap/` | Bootstrap (optional) |
| `profile_photos/` | Uploaded student photos |

Load assets in PHP:

```php
use StudentAttendance\Utils\Assets;

$pageTitle = 'Dashboard';
$assetContext = 'student'; // student | admin | public
require __DIR__ . '/../../components/ui/head.php';
// ... page body ...
require __DIR__ . '/../../components/ui/scripts.php';
```
