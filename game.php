<?php
// فایل ذخیره کاربران
define("USERS_FILE", "users.json");
define("GAME_FILE", "game_data.json");

// بارگذاری اطلاعات کاربران
function load_users() {
    if (!file_exists(USERS_FILE)) {
        file_put_contents(USERS_FILE, json_encode([]));
    }
    return json_decode(file_get_contents(USERS_FILE), true);
}

// ذخیره اطلاعات کاربران
function save_users($users) {
    file_put_contents(USERS_FILE, json_encode($users));
}

// گرفتن موجودی کاربر
function get_balance($user_id) {
    $users = load_users();
    return isset($users[$user_id]) ? $users[$user_id] : 0;
}

// تغییر موجودی کاربر
function update_balance($user_id, $amount) {
    $users = load_users();
    $users[$user_id] = isset($users[$user_id]) ? $users[$user_id] + $amount : $amount;
    save_users($users);
}

// بارگذاری وضعیت بازی‌ها
function load_game() {
    if (!file_exists(GAME_FILE)) {
        file_put_contents(GAME_FILE, json_encode([]));
    }
    return json_decode(file_get_contents(GAME_FILE), true);
}

// ذخیره وضعیت بازی‌ها
function save_game($game_data) {
    file_put_contents(GAME_FILE, json_encode($game_data));
}

// شروع بازی بین دو کاربر
function start_game($player1, $player2) {
    $users = load_users();
    
    if (get_balance($player1) < 500 || get_balance($player2) < 500) {
        return "❌ هر دو بازیکن باید حداقل 500 تومان داشته باشند.";
    }

    $game_data = load_game();

    $game_data[$player1] = [
        "opponent" => $player2,
        "target" => null,
        "attempts" => 5,
        "turn" => "chooser"
    ];

    $game_data[$player2] = [
        "opponent" => $player1,
        "target" => null,
        "attempts" => 5,
        "turn" => "guesser"
    ];

    save_game($game_data);
    return "🎮 بازی شروع شد! بازیکن اول ($player1) باید یک عدد انتخاب کند.";
}

// انتخاب عدد توسط بازیکن اول
function choose_number($user_id, $number) {
    $game_data = load_game();

    if (!isset($game_data[$user_id]) || $game_data[$user_id]["turn"] !== "chooser") {
        return "❌ شما در نوبت انتخاب عدد نیستید.";
    }

    if ($number < 1 || $number > 100) {
        return "❌ عدد باید بین 1 تا 100 باشد.";
    }

    $opponent = $game_data[$user_id]["opponent"];
    $game_data[$user_id]["target"] = $number;
    $game_data[$user_id]["turn"] = "waiting";
    $game_data[$opponent]["turn"] = "guesser";

    save_game($game_data);
    return "✅ عدد ثبت شد. حالا بازیکن دوم باید حدس بزند.";
}

// بررسی حدس بازیکن دوم
function guess_number($user_id, $guess) {
    $game_data = load_game();

    if (!isset($game_data[$user_id]) || $game_data[$user_id]["turn"] !== "guesser") {
        return "❌ شما در نوبت حدس زدن نیستید.";
    }

    $opponent = $game_data[$user_id]["opponent"];
    $target = $game_data[$opponent]["target"];

    if ($guess < 1 || $guess > 100) {
        return "❌ عدد باید بین 1 تا 100 باشد.";
    }

    $game_data[$user_id]["attempts"] -= 1;

    if ($guess == $target) {
        update_balance($user_id, 500);
        update_balance($opponent, -500);
        unset($game_data[$user_id]);
        unset($game_data[$opponent]);
        save_game($game_data);
        return "🏆 تبریک! شما برنده شدید و 500 تومان دریافت کردید!";
    }

    if ($game_data[$user_id]["attempts"] == 0) {
        $game_data[$user_id]["turn"] = "waiting";
        $game_data[$opponent]["turn"] = "chooser";
        save_game($game_data);
        return "❌ شما همه تلاش‌های خود را استفاده کردید. حالا نوبت $opponent است!";
    }

    save_game($game_data);
    $hint = $guess < $target ? "بزرگتر" : "کوچکتر";
    return "🔎 حدس اشتباه! عدد $hint است. شما {$game_data[$user_id]['attempts']} تلاش دیگر دارید.";
}
