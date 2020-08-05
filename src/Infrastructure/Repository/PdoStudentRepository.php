<?php


namespace Alura\Pdo\Infrastructure\Repository;


use Alura\Pdo\Domain\Model\Student;
use Alura\Pdo\Domain\Repository\StudentRepository;
use Alura\Pdo\Infrastructure\Persistence\ConnectionCreator;
use PDO;

class PdoStudentRepository implements StudentRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function allStudents(): array
    {
        $sqlQuery = 'SELECT * FROM students;';
        $statement = $this->connection->query($sqlQuery);
        return $this->hydrateStudentList($statement);
    }

    public function studentsBirthAt(\DateTimeInterface $birthDate): array
    {
        $sqlQuery = 'SELECT * FROM students WHERE birth_date = ?;';
        $statement = $this->connection->query($sqlQuery);
        $statement->bindValue(1, $birthDate->format('Y-m-d'));
        $statement->execute();
        return $this->hydrateStudentList($statement);
    }

    public function save(Student $student): bool
    {
        if($student->id() === null){
            return $this->insert($student);
        }

        return $this->update($student);

    }

    public function remove(Student $student): bool
    {
        $preparedStatement = $this->connection->prepare('DELETE FROM students WHERE id = ?;');
        $preparedStatement->bindValue(1, $student->id(), PDO::PARAM_INT);
        return $preparedStatement->execute();
    }

    private function insert(Student $student)
    {
        $sqlInsert = "INSERT INTO students (name, birth_date) VALUES (:name, :birth_date);";
        $statement = $this->connection->prepare($sqlInsert);
        $success = $statement-> execute([
            ':name' => $student->name(),
            ':birth_date' => $student->birthDate()->format('Y-m-d'),
        ]);

        if($success){
            $student->defineId($this->connection->lastInsertId());
        }

        return $success;
    }

    private function update(Student $student)
    {
        $sqlUpdate = 'UPDATE students SET name = :name, birth_date = :birth_date WHERE id = :id;';
        $statement = $this->connection->prepare($sqlUpdate);
        $statement->bindValue(':name', $student->name());
        $statement->bindValue(':birth_date', $student->birthDate()->format('Y-m-d'));
        $statement->bindValue(':id', $student->id(), PDO::PARAM_INT);

        return $statement->execute();
    }

    private function hydrateStudentList(\PDOStatement $statement) :  array
    {
        $studentDataList = $statement->fetchAll(PDO::FETCH_ASSOC);
        $studentList = [];

        foreach ($studentDataList as $studentData){
            $studentList[] = new Student(
                $studentData['id'],
                $studentData['name'],
                new \DateTimeImmutable($studentData['birth_date'])
            );
        }

        return $studentList;
    }
}