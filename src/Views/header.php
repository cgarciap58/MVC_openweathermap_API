<?php
// header.php
if (session_status() === PHP_SESSION_NONE) session_start();

$current = basename($_SERVER['PHP_SELF']); // e.g. index.php, weather.php
$userName = $_SESSION['user']['name'] ?? 'Account';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'My App') ?></title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Optional: Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="index.php">
      <i class="bi bi-cloud-sun"></i>
      <span>WeatherMVC</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
            aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>" href="index.php">
            Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current === 'weather.php' ? 'active' : '' ?>" href="weather.php">
            Weather
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current === 'about.php' ? 'active' : '' ?>" href="about.php">
            About
          </a>
        </li>
      </ul>

      <form class="d-flex me-lg-3 mb-3 mb-lg-0" role="search" action="weather.php" method="get">
        <div class="input-group">
          <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
          <input class="form-control border-0" type="search" name="q" placeholder="Search city..." aria-label="Search">
          <button class="btn btn-outline-light" type="submit">Go</button>
        </div>
      </form>

      <div class="dropdown">
        <a class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" href="#" role="button"
           data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-person-circle"></i>
          <span class="d-none d-lg-inline"><?= htmlspecialchars($userName) ?></span>
        </a>

        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
          <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<main class="container py-4">