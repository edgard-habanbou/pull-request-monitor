<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    @auth
    <div>
        <h2>Logout</h2>
        <form action="/logout" method="POST">
            @csrf
            <input type="submit" value="Logout">
        </form>
    </div>
    <div>
        <h2>Add Repository</h2>
        <form action="/add-repo" method="POST">
            @csrf
            <input type="text" name="ownerName" placeholder="Owner Name">
            <input type="text" name="repoName" placeholder="Repository Name">
            <input type="submit" value="Add Repository">
        </form>
    </div>
    <div>
        <h2>Repositories</h2>
        <ul>
            @foreach($repositories as $repository)
            <li>
                <h4>
                    {{$repository->owner}}/{{$repository->name}}
                </h4>
                <form action="/delete-repo/{{$repository->id}}" method="POST">
                    @csrf
                    <input type="submit" value="Delete">
                </form>
            </li>
            @endforeach
    </div>
    @else
    <div>
        <h2>Register</h2>
        <form action="/register" method="POST">
            @csrf
            <input type="text" name="name" placeholder="Name">
            <input type="text" name="email" placeholder="Email">
            <input type="password" name="password" placeholder="Password">
            <input type="submit" value="Register">
        </form>
    </div>
    <div>
        <h2>Login</h2>
        <form action="/login" method="POST">
            @csrf
            <input type="text" name="loginEmail" placeholder="Email">
            <input type="password" name="loginPassword" placeholder="Password">
            <input type="submit" value="Login">
        </form>
    </div>
    @endauth



</body>

</html>