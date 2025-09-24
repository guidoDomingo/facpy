$user = new \App\Models\User;
$user->name = "Test User";
$user->email = "test@example.com";
$user->password = \Illuminate\Support\Facades\Hash::make("password123");
$user->save();
echo "Usuario creado: " . $user->id;
exit;
