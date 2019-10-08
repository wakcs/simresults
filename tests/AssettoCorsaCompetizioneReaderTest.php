    <?php
use Simresults\Data_Reader_AssettoCorsaCompetizione;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;
use Simresults\Incident;

/**
 * Tests for the Assetto Corsa Competizione JSON reader
 *
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class AssettoCorsaCompetizioneReaderTest extends PHPUnit_Framework_TestCase {

    /**
     * Set error reporting
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        error_reporting(E_ALL);
    }


    /**
     * Test exception when no data is supplied
     *
     * @expectedException Simresults\Exception\CannotReadData
     */
    public function testCreatingNewAssettoCorsaReaderWithInvalidData()
    {
        $reader = new Data_Reader_AssettoCorsaCompetizione('Unknown data for reader');
    }




    /**
     * Test qualify sessions
     */
    public function testQualifySession()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            '191003_225901_Q.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers
        $this->assertSame('Federico Siv',
            $participants[0]->getDriver()->getName());
        $this->assertSame('Andrea Mel',
            $participants[2]->getDriver()->getName());
    }


    /**
     * Test practice sessions
     */
    public function testPracticeSession()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            '191003_224358_FP.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers
        $this->assertSame('Federico Siv',
            $participants[0]->getDriver()->getName());
        $this->assertSame('Andrea Mel',
            $participants[2]->getDriver()->getName());
    }



    /**
     * Test invalid laps
     */
    public function testInvalidLaps()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            '191003_224358_FP.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());

        // Get participants
        $participants = $session->getParticipants();
        $laps = $participants[0]->getLaps();

        $invalid_lap_keys = array(0,2,3,6,10,12,13,14);
        foreach ($invalid_lap_keys as $invalid_lap) {
            $this->assertNull($laps[$invalid_lap]->getTime());
            $this->assertSame(array(), $laps[$invalid_lap]->getSectorTimes());
        }
    }


    /**
     * Test invalid laps
     */
    public function testPenalties()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            '191003_224358_FP.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());

        // Get participants
        $penalties = $session->getPenalties();

        // Assert drivers
        $this->assertSame('Federico Siv - Cutting - RemoveBestLaptime - violation in lap 3 - cleared in lap 3',
            $penalties[0]->getMessage());
        $this->assertTrue($penalties[0]->isServed());
        $this->assertSame('Federico Siv',$penalties[0]->getParticipant()->getDriver()->getName());

        $this->assertSame('Andrea Mel - Cutting - RemoveBestLaptime - violation in lap 13 - cleared in lap 13',
            $penalties[3]->getMessage());

    }




    /***
    **** Below tests use 1 race log file
    ***/

    /**
     * Test reading the session
     */
    public function testReadingSession()
    {
        // Get session
        $session = $this->getWorkingReader()->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame(23, $session->getLastedLaps());
        $this->assertSame(array(
            'isWetSession' => 1
        ), $session->getOtherSettings());
    }

    /**
     * Test reading the server of a session
     */
    public function testReadingSessionServer()
    {
        // Get the server
        $server = $this->getWorkingReader()->getSession()->getServer();

        // Validate server
        $this->assertSame('Unknown', $server->getName());
    }


    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('Assetto Corsa Competizione', $game->getName());
    }

    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('brands_hatch', $track->getVenue());
    }


    /**
     * Test reading the participants of a session
     */
    public function testReadingSessionParticipants()
    {
        // Get first participant
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();
        $participant = $participants[0];

        $this->assertSame('Andrea Mel',
                          $participant->getDriver()->getName());
        $this->assertSame('Car model 1',
                          $participant->getVehicle()->getName());
        $this->assertSame('',
                          $participant->getTeam());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());
        $this->assertSame(2921.887, $participant->getTotalTime());
    }


    /**
     * Test reading laps of participants
     */
    public function testReadingLapsOfParticipants()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();

        // Get the laps of first participants
        $laps = $participants[0]->getLaps();

        // Validate we have 10 laps
        $this->assertSame(23, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Validate laps
        $this->assertSame(1, $lap->getNumber());
        $this->assertNull($lap->getPosition());
        $this->assertSame(354.586, $lap->getTime());
        $this->assertSame(0, $lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());

        // Get sector times
        $sectors = $lap->getSectorTimes();

        // Validate sectors
        $this->assertSame(281.629, $sectors[0]);
        $this->assertSame(27.762, $sectors[1]);
        $this->assertSame(45.195, $sectors[2]);

        // Second lap
        $lap = $laps[1];
        $this->assertSame(2, $lap->getNumber());
        $this->assertSame(1, $lap->getPosition());
        $this->assertSame(336.123, $lap->getTime());
        $this->assertSame(354.586, $lap->getElapsedSeconds());

        // Validate extra positions
        $laps = $participants[2]->getLaps();
        $this->assertNull($laps[0]->getPosition());
        $this->assertSame(3 , $laps[1]->getPosition());
    }



    /**
     * Get a working reader
     */
    protected function getWorkingReader()
    {
        static $reader;

        // Reader aready created
        if ($reader)
        {
            return $reader;
        }

        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/assettocorsa-competizione/191003_235558_R.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}