<?php
require_once 'class/config.php';
require_once 'class/database.php';
require_once 'steamauth/steamauth.php';
require_once 'class/utils.php';

$db = new DataBase();
if (isset($_SESSION['steamid'])) {

	$steamid = $_SESSION['steamid'];

	$weapons = UtilsClass::getWeaponsFromArray();
	$skins = UtilsClass::skinsFromJson();
    $querySelected = $db->select("
        SELECT `weapon_defindex`, MAX(`weapon_paint_id`) AS `weapon_paint_id`, MAX(`weapon_wear`) AS `weapon_wear`, MAX(`weapon_seed`) AS `weapon_seed`
        FROM `wp_player_skins`
        WHERE `steamid` = :steamid
        GROUP BY `weapon_defindex`, `steamid`
    ", ["steamid" => $steamid]);
	$selectedSkins = UtilsClass::getSelectedSkins($querySelected);
	$selectedKnife = $db->select("SELECT * FROM `wp_player_knife` WHERE `wp_player_knife`.`steamid` = :steamid LIMIT 1", ["steamid" => $steamid]);
	$knifes = UtilsClass::getKnifeTypes();

	if (isset($_POST['forma'])) {
		$ex = explode("-", $_POST['forma']);

		if ($ex[0] == "knife") {
			$db->query("INSERT INTO `wp_player_knife` (`steamid`, `knife`, `weapon_team`) VALUES(:steamid, :knife, 2) ON DUPLICATE KEY UPDATE `knife` = :knife", ["steamid" => $steamid, "knife" => $knifes[$ex[1]]['weapon_name']]);
			$db->query("INSERT INTO `wp_player_knife` (`steamid`, `knife`, `weapon_team`) VALUES(:steamid, :knife, 3) ON DUPLICATE KEY UPDATE `knife` = :knife", ["steamid" => $steamid, "knife" => $knifes[$ex[1]]['weapon_name']]);
		} else {
			if (array_key_exists($ex[1], $skins[$ex[0]]) && isset($_POST['wear']) && $_POST['wear'] >= 0.00 && $_POST['wear'] <= 1.00 && isset($_POST['seed'])) {
				$wear = floatval($_POST['wear']); // wear
				$seed = intval($_POST['seed']); // seed
				if (array_key_exists($ex[0], $selectedSkins)) {
					$db->query("UPDATE wp_player_skins SET weapon_paint_id = :weapon_paint_id, weapon_wear = :weapon_wear, weapon_seed = :weapon_seed WHERE steamid = :steamid AND weapon_defindex = :weapon_defindex", ["steamid" => $steamid, "weapon_defindex" => $ex[0], "weapon_paint_id" => $ex[1], "weapon_wear" => $wear, "weapon_seed" => $seed]);
				} else {
					$db->query("INSERT INTO wp_player_skins (`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_team`) VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 2)", ["steamid" => $steamid, "weapon_defindex" => $ex[0], "weapon_paint_id" => $ex[1], "weapon_wear" => $wear, "weapon_seed" => $seed]);
					$db->query("INSERT INTO wp_player_skins (`steamid`, `weapon_defindex`, `weapon_paint_id`, `weapon_wear`, `weapon_seed`, `weapon_team`) VALUES (:steamid, :weapon_defindex, :weapon_paint_id, :weapon_wear, :weapon_seed, 3)", ["steamid" => $steamid, "weapon_defindex" => $ex[0], "weapon_paint_id" => $ex[1], "weapon_wear" => $wear, "weapon_seed" => $seed]);
				}
			}
		}
		header("Location: {$_SERVER['PHP_SELF']}");
	}
}
?>

<!DOCTYPE html>
<html lang="en"<?php if(WEB_STYLE_DARK) echo 'data-bs-theme="dark"'?>>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="style.css">
	<title>TRIBILINES PAGINA WEB</title>
</head>

<body>

	<?php
	if (!isset($_SESSION['steamid'])) {
	?>
	<div class="landing-hero">
		<div class="container hero-content">
			<div class="hero-copy">
				<span class="hero-badge">Tribilines Skins</span>
				<h1 class="hero-title">Diseña tu loadout antes de entrar al servidor.</h1>
				<p class="hero-subtitle">Selecciona skins, cuchillos con un solo clic.</p>
				<ul class="hero-highlights">
					<li>Previsualizaciones en alta calidad</li>
					<li>Guardado instantáneo en tu cuenta</li>
					<li>!WP para sincronizar tus skins ingame</li>
				</ul>
				<div class="hero-actions">
					<?php loginbutton("rectangle"); ?>
					<span class="hero-hint">Conecta tu cuenta Steam para empezar</span>
				</div>
			</div>
			<div class="hero-illustration">
				<div class="hero-card">
					<div class="hero-card-header">Preview</div>
					<div class="hero-card-body">
						<img src="preview.png" alt="Vista previa de skins" />
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	} else {
		$knifeWeaponNames = [];
		foreach ($knifes as $knifeKey => $knifeMeta) {
			if ($knifeKey === 0) {
				continue;
			}
			$knifeWeaponNames[] = $knifeMeta['weapon_name'];
		}

		$weaponGroups = [
			'knives' => [
				'label' => 'Cuchillos',
				'members' => $knifeWeaponNames
			],
			'rifles' => [
				'label' => 'Fusiles',
				'members' => [
					'weapon_ak47',
					'weapon_m4a1',
					'weapon_m4a1_silencer',
					'weapon_aug',
					'weapon_sg556',
					'weapon_famas',
					'weapon_galilar',
					'weapon_ssg08',
					'weapon_awp',
					'weapon_scar20',
					'weapon_g3sg1'
				]
			],
			'smgs' => [
				'label' => 'Subfusiles',
				'members' => [
					'weapon_mac10',
					'weapon_mp7',
					'weapon_mp9',
					'weapon_mp5sd',
					'weapon_p90',
					'weapon_ump45',
					'weapon_bizon'
				]
			],
			'pistols' => [
				'label' => 'Pistolas',
				'members' => [
					'weapon_glock',
					'weapon_usp_silencer',
					'weapon_hkp2000',
					'weapon_p250',
					'weapon_cz75a',
					'weapon_fiveseven',
					'weapon_elite',
					'weapon_tec9',
					'weapon_deagle',
					'weapon_revolver'
				]
			],
			'heavy' => [
				'label' => 'Pesadas & Escopetas',
				'members' => [
					'weapon_nova',
					'weapon_xm1014',
					'weapon_mag7',
					'weapon_sawedoff',
					'weapon_m249',
					'weapon_negev'
				]
			]
		];

		$categorizedWeapons = [];
		foreach ($weaponGroups as $groupKey => $meta) {
			$categorizedWeapons[$groupKey] = [];
		}
		$categorizedWeapons['others'] = [];

		foreach ($weapons as $defindex => $default) {
			$weaponName = $default['weapon_name'] ?? '';
			$allocated = false;

			foreach ($weaponGroups as $groupKey => $meta) {
				if (in_array($weaponName, $meta['members'], true)) {
					$categorizedWeapons[$groupKey][$defindex] = $default;
					$allocated = true;
					break;
				}
			}

			if (!$allocated) {
				$categorizedWeapons['others'][$defindex] = $default;
			}
		}

		$displayGroups = $weaponGroups;
		if (!empty($categorizedWeapons['others'])) {
			$displayGroups['others'] = [
				'label' => 'Otros',
				'members' => []
			];
		}

		$actualKnife = $knifes[0];
		if ($selectedKnife != null) {
			foreach ($knifes as $knife) {
				if (($selectedKnife[0]['knife'] ?? null) === $knife['weapon_name']) {
					$actualKnife = $knife;
					break;
				}
			}
		}
		$currentKnifeName = trim(explode('|', $actualKnife['paint_name'])[0]);
	?>
	<div class="topbar">
		<div class="container d-flex justify-content-between align-items-center gap-4">
			<div>
				<h2 class="mb-1">Tu loadout de skins</h2>
				<p class="topbar-subtitle mb-0">Organiza tus skins por categoría y ajusta cada detalle al vuelo.</p>
			</div>
			<a class="btn btn-outline-danger btn-sm" href="<?php echo $_SERVER['PHP_SELF']; ?>?logout">Salir</a>
		</div>
	</div>
	<div class="page-wrapper container py-4">
		<div class="row g-4 align-items-start">
			<div class="col-12 col-lg-4 col-xl-3">
				<div class="card modern-card text-center knife-card">
					<div class="card-body d-flex flex-column align-items-center gap-3">
						<span class="knife-title">Cuchillo</span>
						<h5 class="current-knife text-center mb-0"><?php echo htmlspecialchars($currentKnifeName); ?></h5>
						<img src="<?php echo htmlspecialchars($actualKnife['image_url']); ?>" class="skin-image" alt="<?php echo htmlspecialchars($currentKnifeName); ?>">
						<form action="" method="POST" class="w-100 d-flex flex-column gap-3">
							<label class="form-label text-start mb-0">Selecciona un cuchillo</label>
							<select name="forma" class="form-select form-select-sm weapon-select" onchange="this.form.submit()" aria-label="Seleccionar cuchillo">
								<option disabled>Elegir</option>
								<?php
								$selectedKnifeValue = $selectedKnife[0]['knife'] ?? $actualKnife['weapon_name'];
								foreach ($knifes as $knifeKey => $knife) {
									$isKnifeSelected = ($selectedKnifeValue === $knife['weapon_name']);
									$paintLabel = htmlspecialchars($knife['paint_name']);
									echo "<option value=\"knife-{$knifeKey}\"" . ($isKnifeSelected ? ' selected' : '') . ">{$paintLabel}</option>";
								}
								?>
							</select>
						</form>
					</div>
				</div>
			</div>
			<div class="col-12 col-lg-8 col-xl-9">
				<div class="accordion weapon-accordion" id="weaponAccordion">
					<?php
					$groupIndex = 0;
					foreach ($displayGroups as $groupKey => $meta) {
						$groupWeapons = $categorizedWeapons[$groupKey] ?? [];
						if (empty($groupWeapons)) {
							continue;
						}
						$collapseId = 'weapon-group-' . $groupIndex;
						$headingId = 'heading-' . $groupIndex;
						$isFirst = $groupIndex === 0;
						$groupIndex++;
					?>
					<div class="accordion-item">
						<h2 class="accordion-header" id="<?php echo $headingId; ?>">
							<button class="accordion-button <?php echo $isFirst ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapseId; ?>">
								<?php echo htmlspecialchars($meta['label']); ?>
								<span class="badge rounded-pill ms-auto"><?php echo count($groupWeapons); ?></span>
							</button>
						</h2>
						<div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>" aria-labelledby="<?php echo $headingId; ?>">
							<div class="accordion-body">
								<div class="weapons-grid">
									<?php foreach ($groupWeapons as $defindex => $default) :
										$isSelected = array_key_exists($defindex, $selectedSkins);
										$defaultPaintKey = array_key_first($skins[$defindex]);
										if ($defaultPaintKey === null) {
											continue;
										}
										$selectedPaintId = $isSelected ? $selectedSkins[$defindex]['weapon_paint_id'] : $defaultPaintKey;
										$selectedPaint = $skins[$defindex][$selectedPaintId] ?? $skins[$defindex][$defaultPaintKey];
										$displayName = $selectedPaint['paint_name'] ?? $default['paint_name'];
										$nameParts = array_map('trim', explode('|', $displayName));
										$baseName = $nameParts[0] ?? $displayName;
										$skinName = $nameParts[1] ?? '';
										$imageUrl = $selectedPaint['image_url'] ?? $default['image_url'];
										$wearValue = $selectedSkins[$defindex]['weapon_wear'] ?? 0.0;
										$cardClasses = 'weapon-card';
										if ($isSelected) {
											$cardClasses .= ' selected';
										}
									?>
									<div class="<?php echo $cardClasses; ?>">
										<div class="weapon-title">
											<span class="weapon-base"><?php echo strtoupper(str_replace('weapon_', '', $default['weapon_name'])); ?></span>
											<span class="weapon-name"><?php echo htmlspecialchars($baseName); ?></span>
											<?php if (!empty($skinName)) : ?>
											<span class="weapon-skin"><?php echo htmlspecialchars($skinName); ?></span>
											<?php endif; ?>
										</div>
										<div class="weapon-thumb">
											<img src="<?php echo htmlspecialchars($imageUrl); ?>" class="skin-image" alt="<?php echo htmlspecialchars($displayName); ?>">
										</div>
										<form action="" method="POST" class="weapon-form">
											<select name="forma" class="form-select weapon-select" onchange="this.form.submit()" aria-label="Selecciona skin para <?php echo htmlspecialchars($baseName); ?>">
												<option disabled>Selecciona skin</option>
												<?php foreach ($skins[$defindex] as $paintKey => $paint) :
													$optionSelected = ($isSelected && $selectedPaintId == $paintKey);
												?>
												<option value="<?php echo $defindex . '-' . $paintKey; ?>" <?php echo $optionSelected ? 'selected' : ''; ?>>
													<?php echo htmlspecialchars($paint['paint_name']); ?>
												</option>
												<?php endforeach; ?>
											</select>
											<div class="weapon-actions">
												<?php if ($isSelected) : ?>
												<button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#weaponModal<?php echo $defindex; ?>">
													Ajustes
												</button>
												<span class="wear-chip">Wear <?php echo number_format((float) $wearValue, 2); ?></span>
												<?php else : ?>
												<button type="button" class="btn btn-outline-secondary btn-sm" onclick="showSkinSelectionAlert()">
													Ajustes
												</button>
												<span class="wear-chip muted">Selecciona una skin</span>
												<?php endif; ?>
											</div>

											<div class="modal fade" id="weaponModal<?php echo $defindex; ?>" tabindex="-1" aria-labelledby="weaponModalLabel<?php echo $defindex; ?>" aria-hidden="true">
												<div class="modal-dialog modal-dialog-centered">
													<div class="modal-content">
														<div class="modal-header">
															<h5 class="modal-title item-name" id="weaponModalLabel<?php echo $defindex; ?>"><?php echo htmlspecialchars($displayName); ?> · Ajustes</h5>
															<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
														</div>
														<div class="modal-body">
															<div class="mb-3">
																<label for="wearSelect<?php echo $defindex; ?>" class="form-label">Desgaste</label>
																<select class="form-select" id="wearSelect<?php echo $defindex; ?>" onchange="handleWearChange(<?php echo $defindex; ?>, this.value)">
																	<option value="0.00" <?php echo ($selectedSkins[$defindex]['weapon_wear'] ?? 0.0) == 0.00 ? 'selected' : ''; ?>>Factory New · 0.00</option>
																	<option value="0.07" <?php echo ($selectedSkins[$defindex]['weapon_wear'] ?? 0.0) == 0.07 ? 'selected' : ''; ?>>Minimal Wear · 0.07</option>
																	<option value="0.15" <?php echo ($selectedSkins[$defindex]['weapon_wear'] ?? 0.0) == 0.15 ? 'selected' : ''; ?>>Field-Tested · 0.15</option>
																	<option value="0.38" <?php echo ($selectedSkins[$defindex]['weapon_wear'] ?? 0.0) == 0.38 ? 'selected' : ''; ?>>Well-Worn · 0.38</option>
																	<option value="0.45" <?php echo ($selectedSkins[$defindex]['weapon_wear'] ?? 0.0) == 0.45 ? 'selected' : ''; ?>>Battle-Scarred · 0.45</option>
																</select>
															</div>
															<div class="row g-2">
																<div class="col-6">
																	<label for="wear<?php echo $defindex; ?>" class="form-label">Valor exacto</label>
																	<input type="text" value="<?php echo $selectedSkins[$defindex]['weapon_wear'] ?? 0.0; ?>" class="form-control" id="wear<?php echo $defindex; ?>" name="wear">
																</div>
																<div class="col-6">
																	<label for="seed<?php echo $defindex; ?>" class="form-label">Seed</label>
																	<input type="number" value="<?php echo $selectedSkins[$defindex]['weapon_seed'] ?? 0; ?>" class="form-control" id="seed<?php echo $defindex; ?>" name="seed" oninput="validateSeed(this)">
																</div>
															</div>
														</div>
														<div class="modal-footer">
															<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
															<button type="submit" class="btn btn-danger">Aplicar</button>
														</div>
													</div>
												</div>
											</div>
										</form>
									</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
	<?php
	}
	?>
	<script>
		function showSkinSelectionAlert() {
			alert('Selecciona una skin antes de abrir los ajustes.');
		}

		function handleWearChange(defindex, value) {
			var input = document.getElementById('wear' + defindex);
			if (input) {
				input.value = value;
			}
		}

		function validateSeed(input) {
			var val = parseInt(input.value, 10);
			if (isNaN(val)) {
				val = 0;
			}
			val = Math.max(0, Math.min(1000, val));
			input.value = val;
		}
	</script>
	<div class="container">
		<footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
			<div class="col-md-4 d-flex align-items-center">
				<span class="mb-3 mb-md-0 text-body-secondary">© 2025 <a href="https://github.com/nsh1z"> hecho con amor por nsh ❤️</a></span>
			</div>
		</footer>
	</div>
</body>

</html>
